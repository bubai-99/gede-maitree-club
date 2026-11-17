<?php

namespace App\Passes;

use App\Enums\ScanMethod;
use App\Enums\ScanResult;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Pass;
use App\Models\PassScan;
use App\Models\User;
use App\Support\QrCode;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The gate: checks a pass QR, a member card QR or a typed family number against an event and
 * lets the family in. The pass row is locked (SELECT ... FOR UPDATE) while it is checked and
 * marked used, so two gates scanning the same pass at the same moment admit it only once.
 *
 * Scans made while a phone was offline arrive later through sync() with what the phone decided
 * (local_result). The people the phone let in did go in, so their scan is kept as "allowed";
 * the server then keeps the earliest admission as the pass's entry (first scan wins) and marks
 * later admissions of the same pass as duplicates for the committee to see.
 */
class GateKeeper
{
    /**
     * @param  array{uuid: string, code?: ?string, family_no?: ?string, gate?: ?string, scanned_at?: ?CarbonInterface, offline?: bool, local_result?: ?ScanResult, people?: ?int, let_in?: bool}  $input
     *
     * @throws GateException
     */
    public function scan(Event $event, User $by, array $input): PassScan
    {
        $existing = PassScan::query()->where('uuid', $input['uuid'])->first();

        if ($existing !== null) {
            if ($existing->event_id !== $event->id || $existing->scanned_by !== $by->id) {
                throw new GateException('This scan was already uploaded for another event or by someone else.');
            }

            return $existing;
        }

        $offline = $input['offline'] ?? false;
        $scannedAt = $this->scanTime($input['scanned_at'] ?? null, $offline);
        [$method, $pass, $family, $denial] = $this->resolve($event, $input);

        return DB::transaction(function () use ($event, $by, $input, $offline, $scannedAt, $method, $pass, $family, $denial): PassScan {
            $scan = new PassScan([
                'uuid' => $input['uuid'],
                'event_id' => $event->id,
                'family_id' => $family?->id,
                'scanned_by' => $by->id,
                'gate' => $input['gate'] ?? null,
                'method' => $method,
                'offline' => $offline,
                'scanned_at' => $scannedAt,
                'people' => 0,
                'let_in' => false,
            ]);

            $local = $offline ? ($input['local_result'] ?? null) : null;

            if ($pass === null) {
                $this->decideWithoutPass($scan, $denial ?? ScanResult::Invalid, $local, $input);
                $scan->save();

                return $scan;
            }

            $locked = Pass::query()->whereKey($pass->id)->lockForUpdate()->firstOrFail();
            $scan->pass_id = $locked->id;
            $usable = $locked->isActive() && ! Event::query()->whereKey($event->id)->whereNotNull('cancelled_at')->exists();

            if ($offline && $local !== null) {
                $this->recordOffline($scan, $locked, $usable, $local, $input);
            } elseif (! $usable) {
                $scan->result = ScanResult::Cancelled;
            } elseif ($locked->used_at === null) {
                $scan->result = ScanResult::Allowed;
                $scan->people = $this->headCount($input['people'] ?? null, $locked);
                $this->makeEntry($locked, $scan);
            } else {
                $scan->result = ScanResult::Already;
            }

            $scan->save();

            return $scan;
        });
    }

    /**
     * Change how many people of an admitted family came in (within the undo window).
     *
     * @throws GateException
     */
    public function setPeople(PassScan $scan, User $by, int $people, ?CarbonInterface $at = null): PassScan
    {
        return DB::transaction(function () use ($scan, $by, $people, $at): PassScan {
            [$locked, $pass] = $this->lockScan($scan, $by, $at);

            if ($locked->result !== ScanResult::Allowed || $pass === null) {
                throw new GateException('Only a family that was let in has a head count.');
            }

            $locked->forceFill(['people' => $this->headCount($people, $pass)])->save();

            if ($this->isEntry($locked, $pass)) {
                $pass->forceFill(['entered_people' => $locked->people])->save();
            }

            return $locked;
        });
    }

    /**
     * Someone who had already gone in stepped out and is let back in.
     *
     * @throws GateException
     */
    public function letBackIn(PassScan $scan, User $by, ?CarbonInterface $at = null): PassScan
    {
        return DB::transaction(function () use ($scan, $by, $at): PassScan {
            [$locked] = $this->lockScan($scan, $by, $at);

            if ($locked->result !== ScanResult::Already) {
                throw new GateException('Only someone already inside can be let back in.');
            }

            $locked->forceFill(['let_in' => true])->save();

            return $locked;
        });
    }

    /**
     * Undo a scan made by mistake (only by the person who scanned, within two minutes). If it
     * was the pass's entry, the pass is free again (or the next admission becomes the entry).
     *
     * @throws GateException
     */
    public function undo(PassScan $scan, User $by, ?CarbonInterface $at = null): PassScan
    {
        return DB::transaction(function () use ($scan, $by, $at): PassScan {
            [$locked, $pass] = $this->lockScan($scan, $by, $at);

            $locked->forceFill(['undone_at' => now()])->save();

            if ($pass !== null && $locked->result === ScanResult::Allowed) {
                $this->electEntry($pass);
            }

            activity('gate')->performedOn($locked)->causedBy($by)
                ->withProperties(['event_id' => $locked->event_id, 'result' => $locked->result->value, 'people' => $locked->people])
                ->event('undone')
                ->log('Gate scan undone');

            return $locked;
        });
    }

    /**
     * Upload what an offline phone did, in order. Each operation is answered on its own, so one
     * bad line does not stop the rest.
     *
     * @param  list<array{op: string, uuid: string, code?: ?string, family_no?: ?string, gate?: ?string, scanned_at?: ?CarbonInterface, at?: ?CarbonInterface, local_result?: ?ScanResult, people?: ?int, let_in?: bool}>  $ops
     * @return list<array<string, mixed>>
     */
    public function sync(Event $event, User $by, array $ops): array
    {
        $results = [];

        foreach ($ops as $op) {
            try {
                $scan = match ($op['op']) {
                    'scan' => $this->scan($event, $by, [...$op, 'offline' => true]),
                    'people' => $this->setPeople($this->find($event, $op['uuid']), $by, (int) ($op['people'] ?? 1), $op['at'] ?? null),
                    'let_in' => $this->letBackIn($this->find($event, $op['uuid']), $by, $op['at'] ?? null),
                    'undo' => $this->undo($this->find($event, $op['uuid']), $by, $op['at'] ?? null),
                    default => throw new GateException('Unknown change.'),
                };

                $results[] = ['uuid' => $op['uuid'], 'op' => $op['op'], 'ok' => true, ...$this->present($scan->refresh())];
            } catch (GateException $e) {
                $results[] = ['uuid' => $op['uuid'], 'op' => $op['op'], 'ok' => false, 'error' => $e->translated()];
            }
        }

        return $results;
    }

    /**
     * What the scanner shows for a scan.
     *
     * @return array<string, mixed>
     */
    public function present(PassScan $scan): array
    {
        $scan->loadMissing(['family', 'pass.scanner']);
        $pass = $scan->pass;
        $family = $scan->family;

        return [
            'uuid' => $scan->uuid,
            'result' => $scan->result->value,
            'label' => $scan->result->label(),
            'family_no' => $family?->family_no,
            'name' => $family?->tr('head_name'),
            'people' => $scan->people,
            'max_people' => $pass?->people,
            'let_in' => $scan->let_in,
            'conflict' => $scan->conflict,
            'undone' => $scan->isUndone(),
            'scanned_at' => $scan->scanned_at->toIso8601String(),
            'undo_until' => $scan->scanned_at->copy()->addSeconds(PassScan::UNDO_SECONDS)->toIso8601String(),
            'entry' => $pass !== null && $pass->used_at !== null && ! $this->isEntry($scan, $pass) ? [
                'at' => $pass->used_at->toIso8601String(),
                'time' => local_date($pass->used_at, 'g:i a'),
                'by' => $pass->scanner?->name,
                'gate' => $pass->gate,
            ] : null,
        ];
    }

    /**
     * Counts for the scanner header and the console: people inside, families in, turned away.
     *
     * @return array{inside: int, families: int, denied: int, duplicates: int, reentries: int}
     */
    public function stats(Event $event): array
    {
        $live = fn () => PassScan::query()->where('event_id', $event->id)->whereNull('undone_at');

        return [
            'inside' => (int) $live()->where('result', ScanResult::Allowed->value)->sum('people'),
            'families' => (int) $live()->where('result', ScanResult::Allowed->value)->distinct()->count('pass_id'),
            'denied' => $live()->whereIn('result', ScanResult::deniedValues())->count(),
            'duplicates' => $live()->where('conflict', PassScan::CONFLICT_DUPLICATE)->count(),
            'reentries' => $live()->where('let_in', true)->count(),
        ];
    }

    /**
     * One volunteer's shift at one event: scans, people let in, turned away.
     *
     * @return array{scans: int, people: int, denied: int}
     */
    public function shift(Event $event, User $by, CarbonInterface $since): array
    {
        $mine = fn () => PassScan::query()->where('event_id', $event->id)->where('scanned_by', $by->id)
            ->whereNull('undone_at')->where('created_at', '>=', $since);

        return [
            'scans' => $mine()->count(),
            'people' => (int) $mine()->where('result', ScanResult::Allowed->value)->sum('people'),
            'denied' => $mine()->whereIn('result', ScanResult::deniedValues())->count(),
        ];
    }

    /**
     * The pass list a phone keeps for offline checking: SHA-256 hashes of the pass tokens,
     * family numbers, member card numbers and head counts. No names.
     *
     * @return list<array{h: string, f: string, n: int, s: string, u: bool, m: list<string>}>
     */
    public function offlineList(Event $event): array
    {
        $passes = Pass::query()->where('event_id', $event->id)->with('family.members:id,family_id,member_no')->orderBy('id')->get();

        return $passes->map(fn (Pass $pass): array => [
            'h' => $pass->token_hash,
            'f' => $pass->family->family_no,
            'n' => $pass->people,
            's' => $pass->isActive() && ! $event->isCancelled() ? 'active' : 'cancelled',
            'u' => $pass->used_at !== null,
            'm' => $pass->family->members->pluck('member_no')->filter()->map(fn ($no): string => (string) $no)->values()->all(),
        ])->values()->all();
    }

    /**
     * Find the pass (or say why there is none) from what was scanned or typed.
     *
     * @param  array{code?: ?string, family_no?: ?string}  $input
     * @return array{0: ScanMethod, 1: ?Pass, 2: ?Family, 3: ?ScanResult}
     */
    private function resolve(Event $event, array $input): array
    {
        $code = trim((string) ($input['code'] ?? ''));

        if ($code === '') {
            $family = self::findFamily((string) ($input['family_no'] ?? ''));

            return $this->forFamily(ScanMethod::Typed, $event, $family);
        }

        $token = Pass::tokenFromCode($code);

        if ($token !== null) {
            $pass = Pass::query()->where('token_hash', Pass::hashToken($token))->with('family')->first();

            if ($pass === null) {
                return [ScanMethod::Pass, null, null, ScanResult::Invalid];
            }

            if ($pass->event_id !== $event->id) {
                return [ScanMethod::Pass, null, $pass->family, ScanResult::WrongEvent];
            }

            return [ScanMethod::Pass, $pass, $pass->family, null];
        }

        $memberNo = QrCode::verifyMemberCode($code);

        if ($memberNo !== null) {
            $member = Member::query()->where('member_no', $memberNo)->with('family')->first();

            return $this->forFamily(ScanMethod::Card, $event, $member?->family);
        }

        return [ScanMethod::Pass, null, null, ScanResult::Invalid];
    }

    /**
     * @return array{0: ScanMethod, 1: ?Pass, 2: ?Family, 3: ?ScanResult}
     */
    private function forFamily(ScanMethod $method, Event $event, ?Family $family): array
    {
        if ($family === null) {
            return [$method, null, null, ScanResult::Invalid];
        }

        $pass = Pass::query()->where('event_id', $event->id)->where('family_id', $family->id)->first();

        return [$method, $pass, $family, $pass === null ? ScanResult::NoPass : null];
    }

    /**
     * A typed family number: "104", "0104" or "MC-0104".
     */
    public static function findFamily(string $typed): ?Family
    {
        $typed = strtoupper(trim($typed));

        if ($typed === '') {
            return null;
        }

        if (preg_match('/^\d{1,6}$/', $typed) === 1) {
            $typed = 'MC-'.str_pad($typed, 4, '0', STR_PAD_LEFT);
        }

        return Family::query()->where('family_no', $typed)->first();
    }

    /**
     * @param  array{people?: ?int, let_in?: bool}  $input
     */
    private function decideWithoutPass(PassScan $scan, ScanResult $denial, ?ScanResult $local, array $input): void
    {
        // The phone let them in on its own list, but the server knows no valid pass.
        if ($local === ScanResult::Allowed) {
            $scan->result = ScanResult::Allowed;
            $scan->people = max(1, min(99, (int) ($input['people'] ?? 1)));
            $scan->conflict = PassScan::CONFLICT_CANCELLED;

            return;
        }

        $scan->result = $local !== null && $local !== ScanResult::Already ? $local : $denial;
    }

    /**
     * @param  array{people?: ?int, let_in?: bool}  $input
     */
    private function recordOffline(PassScan $scan, Pass $pass, bool $usable, ScanResult $local, array $input): void
    {
        if ($local !== ScanResult::Allowed) {
            $scan->result = $local;
            $scan->let_in = $local === ScanResult::Already && ($input['let_in'] ?? false);

            return;
        }

        $scan->result = ScanResult::Allowed;
        $scan->people = $this->headCount($input['people'] ?? null, $pass);

        if (! $usable) {
            $scan->conflict = PassScan::CONFLICT_CANCELLED;

            return;
        }

        if ($pass->used_at === null) {
            $this->makeEntry($pass, $scan);

            return;
        }

        // First scan wins: the earlier admission is the pass's entry, the later one a duplicate.
        if ($scan->scanned_at->lessThan($pass->used_at)) {
            PassScan::query()->where('pass_id', $pass->id)->where('result', ScanResult::Allowed->value)
                ->whereNull('undone_at')->whereNull('conflict')
                ->update(['conflict' => PassScan::CONFLICT_DUPLICATE, 'updated_at' => now()]);
            $this->makeEntry($pass, $scan);

            return;
        }

        $scan->conflict = PassScan::CONFLICT_DUPLICATE;
    }

    private function makeEntry(Pass $pass, PassScan $scan): void
    {
        $pass->forceFill([
            'used_at' => $scan->scanned_at,
            'scanned_by' => $scan->scanned_by,
            'gate' => $scan->gate,
            'entered_people' => $scan->people,
        ])->save();
    }

    /**
     * After an undo: the earliest admission left becomes the entry, or the pass is unused again.
     */
    private function electEntry(Pass $pass): void
    {
        $next = PassScan::query()->where('pass_id', $pass->id)->where('result', ScanResult::Allowed->value)
            ->whereNull('undone_at')
            ->where(fn ($q) => $q->whereNull('conflict')->orWhere('conflict', PassScan::CONFLICT_DUPLICATE))
            ->orderBy('scanned_at')->orderBy('id')->first();

        if ($next === null) {
            $pass->forceFill(['used_at' => null, 'scanned_by' => null, 'gate' => null, 'entered_people' => null])->save();

            return;
        }

        $next->forceFill(['conflict' => null])->save();
        $this->makeEntry($pass, $next);
    }

    /**
     * The scan that let this pass in. Of a pass's admissions only the entry has no conflict:
     * later ones are duplicates, ones the server would have refused are marked cancelled.
     */
    private function isEntry(PassScan $scan, Pass $pass): bool
    {
        return $scan->pass_id === $pass->id && $scan->result === ScanResult::Allowed && ! $scan->isUndone() && $scan->conflict === null;
    }

    /**
     * @return array{0: PassScan, 1: ?Pass}
     *
     * @throws GateException
     */
    private function lockScan(PassScan $scan, User $by, ?CarbonInterface $at): array
    {
        $pass = $scan->pass_id !== null ? Pass::query()->whereKey($scan->pass_id)->lockForUpdate()->first() : null;
        $locked = PassScan::query()->whereKey($scan->id)->lockForUpdate()->firstOrFail();

        if ($locked->scanned_by !== $by->id) {
            throw new GateException('Only the person who scanned can change this scan.');
        }

        if ($locked->isUndone()) {
            throw new GateException('This scan was already undone.');
        }

        $when = $at !== null ? Carbon::instance($at)->min(now()) : now();

        if ($when->greaterThan($locked->scanned_at->copy()->addSeconds(PassScan::UNDO_SECONDS))) {
            throw new GateException('Too late to change this scan: it is more than two minutes old.');
        }

        return [$locked, $pass];
    }

    /**
     * @throws GateException
     */
    private function find(Event $event, string $uuid): PassScan
    {
        $scan = PassScan::query()->where('uuid', $uuid)->where('event_id', $event->id)->first();

        if ($scan === null) {
            throw new GateException('This scan has not reached the server yet.');
        }

        return $scan;
    }

    private function headCount(?int $people, Pass $pass): int
    {
        return max(1, min($pass->people, $people ?? $pass->people));
    }

    /**
     * Offline scans keep the phone's time, within reason (not in the future, not days old).
     */
    private function scanTime(?CarbonInterface $at, bool $offline): Carbon
    {
        if (! $offline || $at === null) {
            return now();
        }

        $at = Carbon::instance($at);

        return $at->greaterThan(now()) ? now() : $at->max(now()->subDays(2));
    }
}
