<?php

namespace App\Passes;

use App\Enums\FamilyStatus;
use App\Enums\NoticeAudience;
use App\Enums\PassStatus;
use App\Enums\PublishStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\Notice;
use App\Models\Pass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Family registrations for events and their gate passes.
 *
 * The event row is locked (SELECT ... FOR UPDATE) while a registration is made or changed, so
 * two families taking the last seats at the same moment cannot both get them.
 */
class Registrations
{
    /**
     * Register a family (or change how many are coming). Members may only do this while the
     * event takes registrations; the committee may also register a family after the cut-off.
     *
     * @throws RegistrationException
     */
    public function register(Event $event, Family $family, int $people, User $by, bool $byCommittee = false): EventRegistration
    {
        if ($people < 1) {
            throw new RegistrationException('Choose how many people are coming.');
        }

        return DB::transaction(function () use ($event, $family, $people, $by, $byCommittee): EventRegistration {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw new RegistrationException('This event has been cancelled.');
            }

            if (! $byCommittee && ! $locked->acceptsRegistrations()) {
                throw new RegistrationException('Registration for this event is closed.');
            }

            if ($locked->members_only && $family->status !== FamilyStatus::Active) {
                throw new RegistrationException('Only families with active membership can register for this event.');
            }

            if ($people > $locked->familyLimit()) {
                throw new RegistrationException('A family can register up to :count people.', ['count' => $locked->familyLimit()]);
            }

            $registration = EventRegistration::query()
                ->where('event_id', $locked->id)->where('family_id', $family->id)
                ->lockForUpdate()->first();

            if ($locked->capacity !== null) {
                $others = (int) EventRegistration::query()
                    ->where('event_id', $locked->id)
                    ->where('family_id', '!=', $family->id)
                    ->where('status', RegistrationStatus::Registered->value)
                    ->sum('people');

                if ($others + $people > $locked->capacity) {
                    throw new RegistrationException('Only :count places are left.', ['count' => max(0, $locked->capacity - $others)]);
                }
            }

            $wasActive = $registration?->isActive() ?? false;

            $registration ??= new EventRegistration(['event_id' => $locked->id, 'family_id' => $family->id]);
            $registration->fill([
                'people' => $people,
                'status' => RegistrationStatus::Registered,
                'registered_by' => $wasActive ? $registration->registered_by : $by->id,
                'cancelled_at' => null,
                'cancelled_by' => null,
            ])->save();

            if ($locked->pass_required) {
                $this->issuePass($registration);
            }

            activity('events')->performedOn($registration)->causedBy($by)
                ->withProperties(['event' => $locked->slug, 'family' => $family->family_no, 'people' => $people])
                ->event($wasActive ? 'updated' : 'registered')
                ->log("{$family->family_no} registered {$people} for {$locked->title}");

            return $registration;
        });
    }

    /**
     * Cancel a registration. Members may do this until the cut-off; the pass stops working.
     *
     * @throws RegistrationException
     */
    public function cancel(EventRegistration $registration, User $by, bool $byCommittee = false): EventRegistration
    {
        return DB::transaction(function () use ($registration, $by, $byCommittee): EventRegistration {
            $event = Event::query()->whereKey($registration->event_id)->lockForUpdate()->firstOrFail();
            $locked = EventRegistration::query()->whereKey($registration->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw new RegistrationException('This registration is already cancelled.');
            }

            if (! $byCommittee && ($event->isCancelled() || ! now()->lessThan($event->cutoff()))) {
                throw new RegistrationException('Registrations can no longer be cancelled in the app. Please call the committee.');
            }

            $locked->forceFill(['status' => RegistrationStatus::Cancelled, 'cancelled_at' => now(), 'cancelled_by' => $by->id])->save();
            Pass::query()->where('registration_id', $locked->id)->update(['status' => PassStatus::Cancelled->value, 'updated_at' => now()]);

            activity('events')->performedOn($locked)->causedBy($by)
                ->withProperties(['event' => $event->slug, 'family_id' => $locked->family_id])
                ->event('cancelled')
                ->log("Registration cancelled for {$event->title}");

            return $locked;
        });
    }

    /**
     * Cancel the whole event: registration closes, passes stop working at the gate and a pinned
     * notice tells members in the app. The console then lists WhatsApp links for every family
     * that had registered (nothing is sent by the app itself).
     */
    public function cancelEvent(Event $event, User $by, string $reason, ?string $reasonBn): Event
    {
        return DB::transaction(function () use ($event, $by, $reason, $reasonBn): Event {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                return $locked;
            }

            $locked->forceFill([
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'cancel_reason_bn' => $reasonBn,
                'cancelled_by' => $by->id,
                'registration_open' => false,
            ])->save();

            Pass::query()->where('event_id', $locked->id)->update(['status' => PassStatus::Cancelled->value, 'updated_at' => now()]);

            // A pinned members-only notice in the app, in both languages.
            Notice::query()->create([
                'slug' => Str::limit($locked->slug, 60, '').'-cancelled-'.now()->format('YmdHis'),
                'title' => 'Cancelled: '.$locked->title,
                'title_bn' => 'বাতিল: '.($locked->title_bn ?: $locked->title),
                'tag' => 'Events',
                'tag_bn' => 'অনুষ্ঠান',
                'summary' => $reason,
                'summary_bn' => $reasonBn ?: $reason,
                'audience' => NoticeAudience::Members,
                'pinned' => true,
                'status' => PublishStatus::Published,
                'published_at' => now(),
            ]);

            activity('events')->performedOn($locked)->causedBy($by)
                ->withProperties(['reason' => $reason])
                ->event('cancelled')
                ->log("Event cancelled: {$locked->title}");

            return $locked;
        });
    }

    /**
     * When "pass required" is switched on after families registered, give each of them a pass
     * so nobody is stopped at the gate. Returns how many passes were issued.
     */
    public function issueMissingPasses(Event $event): int
    {
        if (! $event->pass_required || $event->cancelled_at !== null) {
            return 0;
        }

        return DB::transaction(function () use ($event): int {
            $missing = $event->registrations()
                ->where('status', RegistrationStatus::Registered->value)
                ->whereDoesntHave('pass', fn ($pass) => $pass->where('status', PassStatus::Active->value))
                ->get();

            $missing->each(fn (EventRegistration $registration): Pass => $this->issuePass($registration));

            return $missing->count();
        });
    }

    /**
     * One pass per registration. A cancelled pass that comes back gets a new token, so an old
     * screenshot cannot be used.
     */
    private function issuePass(EventRegistration $registration): Pass
    {
        $pass = Pass::query()->where('registration_id', $registration->id)->lockForUpdate()->first();

        if ($pass !== null && $pass->isActive()) {
            $pass->forceFill(['people' => $registration->people])->save();

            return $pass;
        }

        $token = Pass::newToken();
        $attributes = [
            'event_id' => $registration->event_id,
            'family_id' => $registration->family_id,
            'token' => $token,
            'token_hash' => Pass::hashToken($token),
            'people' => $registration->people,
            'status' => PassStatus::Active,
        ];

        if ($pass === null) {
            return Pass::query()->create(['registration_id' => $registration->id, ...$attributes]);
        }

        $pass->forceFill($attributes)->save();

        return $pass;
    }
}
