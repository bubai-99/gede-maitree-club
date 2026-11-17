<?php

namespace Database\Seeders;

use App\Enums\BookCode;
use App\Enums\DueStatus;
use App\Enums\Gateway;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Models\Book;
use App\Models\Due;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentService;
use App\Support\ReceiptNumbers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * SAMPLE money so the fee and khata screens are not empty: this year's receipt register in
 * the Club book (numbers in order, as the prototype shows: up to MC-26-0412), the khata lines
 * that go with them, a few expenses, deposits and the catering and nursery books, one online
 * (Razorpay) payment and one UPI payment waiting for the treasurer. Every note says "Sample".
 */
class SampleAccountsSeeder extends Seeder
{
    /** Last receipt number of the sample register (the prototype's register ends at 0412). */
    private const LAST_RECEIPT = 412;

    public function run(): void
    {
        if (LedgerEntry::query()->exists()) {
            return;
        }

        $treasurer = User::query()->where('email', config('sample.admin_email'))->first();
        $club = Book::forCode(BookCode::Club);

        DB::transaction(function () use ($treasurer, $club): void {
            $year = $this->registerYear();
            $this->sampleHistory($treasurer);
            $this->register($year, $treasurer, $club);
            $this->onlineAndWaiting($treasurer);
            $this->expenses($treasurer);
        });
    }

    /**
     * Year of the sample receipts (MC-26-…), from the sample family's history.
     */
    private function registerYear(): int
    {
        $receipt = Payment::query()->whereNotNull('receipt_no')->orderBy('receipt_no')->value('receipt_no');

        return is_string($receipt) && preg_match('/^MC-(\d{2})-/', $receipt, $m) ? 2000 + (int) $m[1] : (int) now()->format('Y');
    }

    /**
     * The sample family's three receipts get a reference, the treasurer and their khata lines.
     */
    private function sampleHistory(?User $treasurer): void
    {
        foreach (Payment::query()->whereNotNull('receipt_no')->with('family')->get() as $payment) {
            $payment->forceFill([
                'reference' => PaymentService::reference($payment),
                'received_by' => $treasurer?->id,
            ])->save();

            $this->income($payment, $treasurer);
        }
    }

    /**
     * Fill the register: one receipt per number from 0001 to 0412, dated through the year.
     * The other sample families' paid months are covered by their latest receipt.
     */
    private function register(int $year, ?User $treasurer, Book $club): void
    {
        $taken = Payment::query()->whereNotNull('receipt_no')->pluck('paid_at', 'receipt_no');
        $anchors = [0 => Carbon::create($year, 1, 2, 18, 0)];

        foreach ($taken as $no => $paidAt) {
            $anchors[(int) substr((string) $no, -4)] = Carbon::parse($paidAt);
        }

        $end = now()->subDay()->setTime(19, 30);
        $anchors[self::LAST_RECEIPT + 1] = $end->year > $year ? Carbon::create($year, 12, 31, 19, 30) : $end;
        ksort($anchors);

        $families = Family::query()->where('family_no', '!=', 'MC-0104')->orderBy('family_no')->get();
        $paidDues = Due::query()->where('status', DueStatus::Paid->value)->whereIn('family_id', $families->modelKeys())
            ->whereDoesntHave('payments')->orderBy('period')->get()->groupBy('family_id');

        // The last numbers (just before 0412) settle each family's paid months.
        $settling = $families->filter(fn (Family $f): bool => $paidDues->has($f->id))->values();
        $firstSettling = self::LAST_RECEIPT - $settling->count();
        $now = now();
        $rows = [];
        $links = [];

        for ($n = 1; $n <= self::LAST_RECEIPT; $n++) {
            $receipt = ReceiptNumbers::format(BookCode::Club, $year, $n);

            if ($taken->has($receipt)) {
                continue;
            }

            $date = $this->dateFor($n, $anchors);
            $settles = $n >= $firstSettling && $n < self::LAST_RECEIPT ? $settling[$n - $firstSettling] ?? null : null;
            $family = $settles ?? $families[$n % $families->count()];
            $dues = $settles !== null ? $paidDues->get($settles->id) : null;
            $upi = $n % 4 === 0;

            $rows[] = [
                'family_id' => $family->id,
                'amount' => $dues !== null ? (int) $dues->sum('amount') : 100 * (1 + $n % 3),
                'mode' => $upi ? PaymentMode::Upi->value : PaymentMode::Cash->value,
                'gateway' => $upi ? Gateway::ManualUpi->value : null,
                'upi_ref' => $upi ? '5'.str_pad((string) $n, 11, '0', STR_PAD_LEFT) : null,
                'received_by' => $treasurer?->id,
                'receipt_no' => $receipt,
                'status' => PaymentStatus::Confirmed->value,
                'note' => null,
                'paid_at' => $date,
                'created_at' => $date,
                'updated_at' => $now,
            ];

            if ($dues !== null) {
                $links[$receipt] = $dues->modelKeys();
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('payments')->insert($chunk);
        }

        $payments = Payment::query()->whereIn('receipt_no', array_column($rows, 'receipt_no'))->get(['id', 'receipt_no', 'amount', 'mode', 'paid_at', 'family_id']);
        $familyNos = $families->pluck('family_no', 'id');
        $ledger = [];
        $pivot = [];

        foreach ($payments as $payment) {
            DB::table('payments')->where('id', $payment->id)->update(['reference' => PaymentService::reference($payment)]);

            foreach ($links[$payment->receipt_no] ?? [] as $dueId) {
                $pivot[] = ['payment_id' => $payment->id, 'due_id' => $dueId];
            }

            $ledger[] = [
                'book_id' => $club->id,
                'entry_date' => $payment->paid_at?->toDateString(),
                'type' => LedgerType::Income->value,
                'account' => $payment->mode->account()->value,
                'head' => LedgerHead::Subscription->value,
                'amount' => $payment->amount,
                'note' => "{$payment->receipt_no} · {$familyNos[$payment->family_id]} · Sample",
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->id,
                'recorded_by' => $treasurer?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($pivot, 500) as $chunk) {
            DB::table('payment_due')->insert($chunk);
        }

        foreach (array_chunk($ledger, 200) as $chunk) {
            DB::table('ledger_entries')->insert($chunk);
        }

        DB::table('receipt_counters')->upsert(
            [['book_code' => BookCode::Club->value, 'year' => $year, 'last_no' => self::LAST_RECEIPT, 'created_at' => $now, 'updated_at' => $now]],
            ['book_code', 'year'],
            ['last_no', 'updated_at'],
        );
    }

    /**
     * One paid Razorpay payment (receipt issued by the app, with Razorpay's fee as an expense)
     * and one UPI payment waiting for the treasurer.
     */
    private function onlineAndWaiting(?User $treasurer): void
    {
        $payments = app(PaymentService::class);
        $owing = Family::query()->where('family_no', '!=', 'MC-0104')
            ->whereHas('dues', fn ($q) => $q->where('status', DueStatus::Due->value), '>=', 2)
            ->orderBy('family_no')->limit(2)->get();

        if ($owing->count() < 2) {
            return;
        }

        $online = $owing[0];
        $due = $online->dues()->where('status', DueStatus::Due->value)->first();

        if ($due !== null) {
            $payment = Payment::query()->create([
                'family_id' => $online->id,
                'amount' => $due->amount,
                'mode' => PaymentMode::Online,
                'gateway' => Gateway::Razorpay,
                'status' => PaymentStatus::Pending,
            ]);
            $payment->forceFill([
                'reference' => PaymentService::reference($payment),
                'gateway_order_id' => 'order_SAMPLE'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT),
            ])->save();
            $payment->dues()->attach($due->id);

            $payments->confirm($payment, null, [
                'gateway_payment_id' => 'pay_SAMPLE'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT),
                'gateway_method' => 'upi',
                'gateway_fee_paise' => 236,
                'gateway_tax_paise' => 36,
            ]);
        }

        $waiting = $owing[1];
        $due = $waiting->dues()->where('status', DueStatus::Due->value)->first();

        if ($due !== null) {
            $payment = Payment::query()->create([
                'family_id' => $waiting->id,
                'amount' => $due->amount,
                'mode' => PaymentMode::Upi,
                'gateway' => Gateway::ManualUpi,
                'upi_ref' => '612345678901',
                'status' => PaymentStatus::AwaitingConfirmation,
                'submitted_at' => now()->subHours(2),
            ]);
            $payment->forceFill(['reference' => PaymentService::reference($payment)])->save();
            $payment->dues()->attach($due->id);
        }
    }

    /**
     * Sample expenses, deposits and the catering and nursery books for this financial year.
     */
    private function expenses(?User $treasurer): void
    {
        $club = Book::forCode(BookCode::Club);
        $catering = Book::forCode(BookCode::Catering);
        $nursery = Book::forCode(BookCode::Nursery);
        $start = now()->month >= 4 ? Carbon::create(now()->year, 4, 1) : Carbon::create(now()->year - 1, 4, 1);
        $rows = [];

        for ($month = $start->copy(); $month->lte(now()->startOfMonth()); $month->addMonth()) {
            $last = $month->isSameMonth(now()) ? now()->day - 1 : $month->daysInMonth;

            if ($last < 1) {
                continue;
            }

            $day = fn (int $d): string => $month->copy()->day(min($d, $last))->toDateString();

            $rows[] = [$club, $day(8), LedgerType::Expense, LedgerAccount::Bank, LedgerHead::Electricity, 1870, 'CESC bill · Sample'];
            $rows[] = [$club, $day(12), LedgerType::Expense, LedgerAccount::Cash, LedgerHead::Stationery, 240, 'Receipt book and register · Sample'];
            $rows[] = [$club, $day(20), LedgerType::Deposit, LedgerAccount::Bank, LedgerHead::CashTransfer, 8000, 'SBI deposit slip · Sample'];
            $rows[] = [$catering, $day(6), LedgerType::Income, LedgerAccount::Bank, LedgerHead::CateringIncome, 37200, 'Final bill, wedding order · Sample'];
            $rows[] = [$catering, $day(7), LedgerType::Expense, LedgerAccount::Cash, LedgerHead::Groceries, 14650, 'Koley Market · Sample'];
            $rows[] = [$catering, $day(9), LedgerType::Expense, LedgerAccount::Cash, LedgerHead::Wages, 9600, 'Serving team, 12 people · Sample'];
            $rows[] = [$nursery, $day(10), LedgerType::Income, LedgerAccount::Cash, LedgerHead::NurserySales, 4120, 'Counter sales · Sample'];
            $rows[] = [$nursery, $day(15), LedgerType::Expense, LedgerAccount::Cash, LedgerHead::PlantsAndPots, 2350, 'Galiff Street wholesale · Sample'];
        }

        $rows[] = [$club, $start->copy()->addMonths(6)->day(14)->min(now()->subDay())->toDateString(), LedgerType::Expense, LedgerAccount::Bank, LedgerHead::PujaExpenses, 42500, 'Pandal and decoration, Durga Puja · Sample'];
        $rows[] = [$club, $start->copy()->addMonths(5)->day(3)->min(now()->subDay())->toDateString(), LedgerType::Income, LedgerAccount::Bank, LedgerHead::Donation, 15000, 'Donation for the library · Sample'];
        $rows[] = [$club, $start->copy()->addMonths(2)->day(21)->min(now()->subDay())->toDateString(), LedgerType::Expense, LedgerAccount::Cash, LedgerHead::Repairs, 1350, 'Fan repair, club room · Sample'];

        $now = now();

        DB::table('ledger_entries')->insert(array_map(fn (array $row): array => [
            'book_id' => $row[0]->id,
            'entry_date' => $row[1],
            'type' => $row[2]->value,
            'account' => $row[3]->value,
            'head' => $row[4]->value,
            'amount' => $row[5],
            'note' => $row[6],
            'recorded_by' => $treasurer?->id,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows));
    }

    private function income(Payment $payment, ?User $treasurer): void
    {
        $payment->ledgerEntries()->create([
            'book_id' => Book::forCode(BookCode::Club)->id,
            'entry_date' => ($payment->paid_at ?? now())->toDateString(),
            'type' => LedgerType::Income,
            'account' => $payment->mode->account(),
            'head' => LedgerHead::Subscription,
            'amount' => $payment->amount,
            'note' => "{$payment->receipt_no} · {$payment->family->family_no} · Sample",
            'recorded_by' => $treasurer?->id,
        ]);
    }

    /**
     * A date for receipt number $n, spread evenly between the known dates around it.
     *
     * @param  array<int, Carbon>  $anchors
     */
    private function dateFor(int $n, array $anchors): Carbon
    {
        $before = 0;
        $after = self::LAST_RECEIPT + 1;

        foreach (array_keys($anchors) as $key) {
            if ($key <= $n) {
                $before = $key;
            }

            if ($key > $n) {
                $after = $key;
                break;
            }
        }

        $from = $anchors[$before];
        $to = $anchors[$after];
        $share = ($n - $before) / max(1, $after - $before);

        return $from->copy()->addSeconds((int) ($from->diffInSeconds($to) * $share))->setTime(18, 30 + $n % 25);
    }
}
