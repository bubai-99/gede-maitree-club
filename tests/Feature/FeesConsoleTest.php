<?php

namespace Tests\Feature;

use App\Enums\BookCode;
use App\Enums\DueStatus;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Book;
use App\Models\Due;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Khata;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Console fee and khata screens: cash entry, dues, receipts register, khata, reports, roles.
 */
class FeesConsoleTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->family = Family::factory()->create(['family_no' => 'MC-0700', 'head_name' => 'Pradip Saha', 'phone' => '9832469834', 'area' => 'Lane 3']);
        foreach (['2026-08-01', '2026-09-01', '2026-10-01'] as $period) {
            Due::factory()->for($this->family)->create(['period' => $period, 'amount' => 100]);
        }
    }

    private function as(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function treasurerPages(): array
    {
        return [
            'collect' => ['/admin/collect'],
            'online payments' => ['/admin/payments'],
            'import' => ['/admin/payments/import'],
            'dues' => ['/admin/dues'],
            'dues paid' => ['/admin/dues?status=paid'],
            'receipts' => ['/admin/receipts'],
            'khata' => ['/admin/khata'],
            'new entry' => ['/admin/khata/create'],
            'cash' => ['/admin/cash'],
            'reports' => ['/admin/reports'],
        ];
    }

    #[DataProvider('treasurerPages')]
    public function test_the_treasurer_can_open_every_fee_and_khata_page_in_both_languages(string $url): void
    {
        $this->as(Role::Treasurer);

        $this->get($url)->assertOk();
        $this->get($url.(str_contains($url, '?') ? '&' : '?').'lang=bn')->assertOk()->assertSee('<html lang="bn">', false);
    }

    #[DataProvider('treasurerPages')]
    public function test_website_editors_and_volunteers_cannot_open_treasurer_pages(string $url): void
    {
        foreach ([Role::WebsiteEditor, Role::Volunteer, Role::PujaInCharge] as $role) {
            $this->as($role);
            $this->get($url)->assertForbidden();
        }
    }

    public function test_members_cannot_reach_the_treasurer_pages(): void
    {
        $member = User::factory()->forFamily($this->family)->create();
        $member->assignRole(Role::Member->value);

        $this->actingAs($member)->get('/admin/collect')->assertRedirect(route('admin.login'));
        $this->actingAs($member)->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 1, 'mode' => 'cash'])->assertForbidden();
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_the_menu_shows_fee_screens_to_the_treasurer_only(): void
    {
        $this->as(Role::Treasurer);
        $this->get('/admin')->assertSee('Collect payment')->assertSee('Khata')->assertSee('Reports');

        $this->as(Role::WebsiteEditor);
        $this->get('/admin')->assertDontSee('Collect payment')->assertDontSee('Receipts register');
    }

    public function test_finding_a_family_by_name_number_or_phone(): void
    {
        $this->as(Role::Treasurer);

        $this->get('/admin/collect?q=Pradip')->assertSee('MC-0700');
        $this->get('/admin/collect?q=0700')->assertSee('Pradip Saha');
        $this->get('/admin/collect?q=98324')->assertSee('Pradip Saha');
        $this->get('/admin/collect?family='.$this->family->id)->assertSee('clears dues')->assertSee('3 months due');
    }

    public function test_cash_entry_takes_the_oldest_months_and_issues_a_receipt_at_once(): void
    {
        $treasurer = $this->as(Role::Treasurer);

        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 2, 'mode' => 'cash'])->assertRedirect();

        $payment = Payment::query()->sole();
        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(200, $payment->amount);
        $this->assertSame($treasurer->id, $payment->received_by);
        $this->assertSame(['2026-08', '2026-09'], $payment->dues->map(fn (Due $due) => $due->period->format('Y-m'))->all());
        $this->assertSame(1, $this->family->dues()->where('status', DueStatus::Due->value)->count());

        $entry = LedgerEntry::query()->sole();
        $this->assertSame(LedgerAccount::Cash, $entry->account);
        $this->assertSame(200, $entry->amount);

        $this->get('/admin/collect?family='.$this->family->id.'&done='.$payment->id)->assertSee((string) $payment->receipt_no)->assertSee('wa.me/919832469834');
    }

    public function test_cash_entry_beyond_the_dues_is_paid_in_advance(): void
    {
        $this->as(Role::Treasurer);

        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 6, 'mode' => 'cash'])->assertRedirect();

        $payment = Payment::query()->sole();
        $this->assertSame(600, $payment->amount);
        $this->assertSame('2027-01', $payment->dues->last()?->period->format('Y-m'));
        $this->assertSame(6, $this->family->dues()->where('status', DueStatus::Paid->value)->count());
    }

    public function test_upi_at_the_counter_needs_a_unique_utr(): void
    {
        $this->as(Role::Treasurer);

        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 1, 'mode' => 'upi'])->assertSessionHasErrors('utr');
        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 1, 'mode' => 'upi', 'utr' => '412356789012'])->assertSessionHasNoErrors();
        $this->assertSame(LedgerAccount::Bank, LedgerEntry::query()->sole()->account);

        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 1, 'mode' => 'upi', 'utr' => '412356789012'])->assertSessionHasErrors('utr');
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_cancelling_a_receipt_keeps_the_number_and_reverses_the_khata(): void
    {
        $this->as(Role::Treasurer);
        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 3, 'mode' => 'cash']);
        $payment = Payment::query()->sole();

        $this->post('/admin/payments/'.$payment->id.'/cancel', ['reason' => 'Wrong family'])->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Cancelled, $payment->status);
        $this->assertNotNull($payment->receipt_no);
        $this->assertSame(3, $this->family->dues()->where('status', DueStatus::Due->value)->count());
        $this->assertSame(2, LedgerEntry::query()->count());
        $this->assertSame(0, app(Khata::class)->position(Book::forCode(BookCode::Club))['balance']);

        $this->get('/admin/receipts')->assertSee((string) $payment->receipt_no)->assertSee('Cancelled')->assertSee('complete');
        $this->get('/admin/receipts?mode=cancelled')->assertSee('Wrong family');
    }

    public function test_the_receipts_register_reports_missing_numbers(): void
    {
        $this->as(Role::Treasurer);
        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 1, 'mode' => 'cash']);
        DB::table('receipt_counters')->update(['last_no' => 3]);

        $this->get('/admin/receipts')->assertSee('2 missing numbers');
        $this->get('/admin/receipts/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_dues_list_filters_and_reminders(): void
    {
        $this->as(Role::Treasurer);
        $other = Family::factory()->create(['family_no' => 'MC-0701', 'area' => 'Lane 1']);
        Due::factory()->for($other)->create(['period' => '2026-10-01']);

        $this->get('/admin/dues')->assertSee('MC-0700')->assertSee('MC-0701');
        $this->get('/admin/dues?min=3')->assertSee('MC-0700')->assertDontSee('MC-0701');
        $this->get('/admin/dues?area=Lane+1')->assertDontSee('MC-0700')->assertSee('MC-0701');

        // One family: straight to WhatsApp with the message filled in.
        $response = $this->post('/admin/dues/remind', ['families' => [$this->family->id]]);
        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/919832469834?text=', (string) $response->headers->get('Location'));
        $this->assertNotNull($this->family->fresh()?->last_reminded_at);

        // Several: a page of links.
        $this->post('/admin/dues/remind', ['families' => [$this->family->id, $other->id]])->assertOk()->assertSee('wa.me/91', false);
    }

    public function test_waiving_months_needs_a_reason(): void
    {
        $this->as(Role::Treasurer);
        $due = $this->family->dues()->first();

        $this->post('/admin/dues/'.$this->family->id.'/waive', ['dues' => [$due?->id]])->assertSessionHasErrors('reason');
        $this->post('/admin/dues/'.$this->family->id.'/waive', ['dues' => [$due?->id], 'reason' => 'Hardship'])->assertRedirect();

        $due?->refresh();
        $this->assertSame(DueStatus::Waived, $due?->status);
        $this->assertSame('Hardship', $due?->waived_reason);
        $this->get('/admin/dues?status=waived')->assertSee('Hardship');
    }

    public function test_dues_generation_is_idempotent(): void
    {
        $this->as(Role::Treasurer);
        Family::factory()->count(2)->create(['joined_on' => '2020-01-01']);

        $this->post('/admin/dues/generate', ['month' => '2026-12'])->assertRedirect();
        $this->post('/admin/dues/generate', ['month' => '2026-12'])->assertRedirect();
        $this->artisan('dues:generate', ['--month' => '2026-12'])->assertSuccessful();

        $this->assertSame(3, Due::query()->where('period', '2026-12-01')->count());
        $this->assertSame(1, Due::query()->where('period', '2026-12-01')->where('family_id', $this->family->id)->count());
        $this->assertSame(100, Due::query()->where('period', '2026-12-01')->value('amount'));
    }

    public function test_khata_entries_balances_and_reversals(): void
    {
        Storage::fake('local');
        $this->as(Role::Treasurer);
        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 3, 'mode' => 'cash']);

        $this->post('/admin/khata', [
            'book' => 'club', 'type' => 'expense', 'head' => 'electricity', 'account' => 'cash',
            'amount' => 120, 'entry_date' => now()->toDateString(), 'note' => 'CESC bill',
            'bill' => UploadedFile::fake()->image('bill.jpg'),
        ])->assertRedirect();

        $this->post('/admin/khata', [
            'book' => 'club', 'type' => 'deposit', 'account' => 'cash',
            'amount' => 100, 'entry_date' => now()->toDateString(),
        ])->assertRedirect();

        $club = Book::forCode(BookCode::Club);
        $position = app(Khata::class)->position($club);
        $this->assertSame(180, $position['balance']);
        $this->assertSame(80, $position['cash']);
        $this->assertSame(100, $position['bank']);

        $expense = LedgerEntry::query()->where('head', LedgerHead::Electricity->value)->sole();
        $this->assertTrue($expense->hasMedia('bill'));
        $this->get('/admin/khata/'.$expense->id.'/bill')->assertOk();
        $this->get('/admin/khata?book=club')->assertOk()->assertSee('CESC bill')->assertSee('Brought forward');

        $this->post('/admin/khata/'.$expense->id.'/reverse', ['note' => 'Entered twice'])->assertRedirect();
        $this->assertSame(300, app(Khata::class)->position($club)['balance']);
        $this->post('/admin/khata/'.$expense->id.'/reverse', ['note' => 'Again'])->assertSessionHas('error');
    }

    public function test_a_khata_entry_must_use_a_head_of_its_type(): void
    {
        $this->as(Role::Treasurer);

        $this->post('/admin/khata', [
            'book' => 'club', 'type' => 'income', 'head' => 'electricity', 'account' => 'cash',
            'amount' => 100, 'entry_date' => now()->toDateString(),
        ])->assertSessionHasErrors('head');

        $this->post('/admin/khata', [
            'book' => 'club', 'type' => 'expense', 'head' => 'repairs', 'account' => 'cash',
            'amount' => 100, 'entry_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('entry_date');
    }

    public function test_the_catering_in_charge_writes_only_in_the_catering_book(): void
    {
        $this->as(Role::CateringInCharge);

        $this->get('/admin/khata')->assertOk()->assertSee('Catering book');
        $this->get('/admin/khata?book=club')->assertSessionHasErrors('book');
        $this->get('/admin/reports')->assertForbidden();
        $this->get('/admin/collect')->assertForbidden();

        $this->post('/admin/khata', [
            'book' => 'catering', 'type' => 'expense', 'head' => 'groceries', 'account' => 'cash',
            'amount' => 1500, 'entry_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->post('/admin/khata', [
            'book' => 'club', 'type' => 'expense', 'head' => 'repairs', 'account' => 'cash',
            'amount' => 100, 'entry_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(1, LedgerEntry::query()->count());
    }

    public function test_reports_show_collections_and_income_by_head_with_csv(): void
    {
        $this->as(Role::Treasurer);
        $this->post('/admin/collect', ['family_id' => $this->family->id, 'months' => 3, 'mode' => 'cash']);
        app(Khata::class)->record(Book::forCode(BookCode::Club), [
            'type' => LedgerType::Expense, 'head' => LedgerHead::Electricity, 'account' => LedgerAccount::Bank,
            'amount' => 1870, 'entry_date' => now()->toDateString(), 'note' => null,
        ], User::query()->firstOrFail());

        $this->get('/admin/reports')->assertOk()->assertSee('Subscription')->assertSee('Electricity')->assertSee('₹300')->assertSee('₹1,870');

        $csv = $this->get('/admin/reports/income-expenditure.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('"Club book",Income,Subscription,300', $csv);
        $this->assertStringContainsString('"Club book",Expense,Electricity,1870', $csv);

        $collections = $this->get('/admin/reports/collections.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString(now()->format('Y-m').',1,300,0,0,300', $collections);
    }
}
