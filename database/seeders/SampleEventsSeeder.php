<?php

namespace Database\Seeders;

use App\Chanda\ChandaBook;
use App\Enums\BookCode;
use App\Enums\ChandaMode;
use App\Enums\FamilyStatus;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\Role;
use App\Models\Book;
use App\Models\ChandaArea;
use App\Models\ChandaEntry;
use App\Models\Event;
use App\Models\Family;
use App\Models\User;
use App\Passes\Registrations;
use App\Payments\Khata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * SAMPLE events data (M8): volunteer collectors, the puja chanda drive (areas, receipts in the
 * CH series, sponsors, one cash handover, two puja expenses), and family registrations with
 * gate passes for the events that need a pass. Donor names say "Sample". Runs once: it does
 * nothing if chanda receipts already exist.
 */
class SampleEventsSeeder extends Seeder
{
    /** Gift sizes the sample receipts cycle through (rupees). */
    private const GIFTS = [501, 1001, 251, 2001, 1001, 501, 1501];

    public function run(ChandaBook $book, Registrations $registrations, Khata $khata): void
    {
        if (ChandaEntry::query()->exists()) {
            return;
        }

        $data = json_decode(File::get(database_path('seeders/data/prototype.json')), true, flags: JSON_THROW_ON_ERROR);
        $admin = User::query()->where('email', config('sample.admin_email'))->first();

        if ($admin === null) {
            return;
        }

        $collectors = $this->volunteers($data['drive']['areas']);
        $this->registrations($registrations, $admin);

        $event = Event::query()->collectingChanda()->first();

        if ($event === null) {
            return;
        }

        $receipts = $this->chanda($event, $data['drive'], $collectors, $book, $admin);
        $this->expenses($event, $khata, $admin);

        $this->command?->info("Sample chanda: {$receipts} receipts in the CH series for {$event->title}, ".count($data['drive']['sponsors']).' sponsors, '.count($collectors).' sample volunteers.');
    }

    /**
     * One SAMPLE volunteer per chanda area, with a random password nobody knows (use "Forgot
     * password" or set one in tinker to try the volunteer's view).
     *
     * @param  list<array<string, mixed>>  $areas
     * @return array<string, User> lane => collector
     */
    private function volunteers(array $areas): array
    {
        $collectors = [];

        foreach ($areas as $i => $area) {
            $name = (string) $area['volunteer'];
            $user = User::query()->firstOrCreate(
                ['email' => 'volunteer'.($i + 1).'@example.org'],
                ['name' => $name, 'phone' => '98300'.str_pad((string) (10001 + $i), 5, '0', STR_PAD_LEFT), 'is_committee' => true, 'password' => Str::password(24), 'email_verified_at' => now()],
            );
            $user->syncRoles([Role::Volunteer->value]);
            $collectors[(string) $area['lane']] = $user;
        }

        return $collectors;
    }

    /**
     * Sample families register for the events that need a pass (passes are made as they register).
     */
    private function registrations(Registrations $registrations, User $admin): void
    {
        $families = Family::query()->where('status', FamilyStatus::Active->value)->orderBy('family_no')->with('members')->get();
        $events = Event::query()->where('pass_required', true)->where('registration_open', true)->upcoming()->get();

        foreach ($events as $e => $event) {
            foreach ($families as $i => $family) {
                // Every family on the first event, every other family on the next.
                if ($e > 0 && $i % 2 === 1 && $family->family_no !== 'MC-0104') {
                    continue;
                }

                $people = max(1, min($event->familyLimit(), $family->members->count() + ($i % 3)));
                $registrations->register($event, $family, $people, $admin, byCommittee: true);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $drive
     * @param  array<string, User>  $collectors
     */
    private function chanda(Event $event, array $drive, array $collectors, ChandaBook $book, User $admin): int
    {
        $count = 0;

        foreach ($drive['areas'] as $sort => $row) {
            $collector = $collectors[(string) $row['lane']] ?? null;
            $area = ChandaArea::query()->create([
                'event_id' => $event->id,
                'name' => $row['lane'],
                'name_bn' => $row['laneBn'],
                'collector_id' => $collector?->id,
                'target' => $row['target'],
                'houses' => $row['houses'],
                'visited' => $row['visited'],
                'sort' => $sort + 1,
            ]);

            // Receipts that add up to what the lane has collected so far.
            $left = (int) $row['got'];
            $n = 0;

            while ($left > 0) {
                $amount = min($left, self::GIFTS[($n + $sort) % count(self::GIFTS)]);
                $left -= $amount;
                $upi = $n % 4 === 3;

                $book->record($event, [
                    'donor_name' => 'Sample donor '.($n + 1).', '.$row['lane'],
                    'area' => $area,
                    'amount' => $amount,
                    'mode' => $upi ? ChandaMode::Upi : ChandaMode::Cash,
                    'upi_ref' => $upi ? '6310'.str_pad((string) ($sort * 1000 + $n), 8, '0', STR_PAD_LEFT) : null,
                    'show_name' => $n % 3 === 0,
                    'collected_by' => $collector?->id,
                    'received_at' => now()->subDays(14 - min(13, intdiv($n, 3)))->setTime(18, 0),
                ], $collector ?? $admin);

                $n++;
                $count++;
            }
        }

        foreach ($drive['sponsors'] as $row) {
            $sponsor = $event->sponsors()->create([
                'name' => $row['name'],
                'name_bn' => $row['nameBn'],
                'amount' => $row['amount'],
                'deliverable' => $row['gives'],
                'deliverable_bn' => $row['givesBn'],
            ]);

            if ($row['status'] === 'paid') {
                $book->receiveSponsor($sponsor, (int) $row['amount'], ChandaMode::Upi, '6319'.str_pad((string) $sponsor->id, 8, '0', STR_PAD_LEFT), $admin);
                $count++;
            }
        }

        // One collector has already handed her cash to the treasurer.
        $handedOver = $collectors['Lane 2'] ?? null;

        if ($handedOver !== null) {
            $cash = (int) ChandaEntry::query()->valid()->where('collected_by', $handedOver->id)->where('mode', ChandaMode::Cash->value)->sum('amount');
            $book->handOver($handedOver, $cash, null, $admin, $event);
        }

        return $count;
    }

    /**
     * Two advances paid for the puja, marked for the event so the budget shows "spent".
     */
    private function expenses(Event $event, Khata $khata, User $admin): void
    {
        $club = Book::forCode(BookCode::Club);

        foreach ([[5000, 'Sample: advance to the idol maker'], [8000, 'Sample: pandal and lights advance']] as [$amount, $note]) {
            $khata->record($club, [
                'type' => LedgerType::Expense,
                'head' => LedgerHead::PujaExpenses,
                'account' => LedgerAccount::Cash,
                'amount' => $amount,
                'entry_date' => now()->subDays(5)->toDateString(),
                'note' => $note,
                'event_id' => $event->id,
            ], $admin);
        }
    }
}
