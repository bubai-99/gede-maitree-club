<?php

namespace Tests\Feature;

use App\Chanda\ChandaBook;
use App\Chanda\ChandaException;
use App\Enums\BookCode;
use App\Enums\ChandaMode;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\Role;
use App\Enums\SponsorStatus;
use App\Models\Book;
use App\Models\ChandaArea;
use App\Models\ChandaEntry;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\SiteSetting;
use App\Models\Sponsor;
use App\Models\User;
use App\Payments\Khata;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * M8 chanda book: receipts in the CH series, the Club book, volunteers in their own area,
 * sponsors, cash handover, budget and what the website shows.
 */
class ChandaBookTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private ChandaArea $lane1;

    private ChandaArea $lane2;

    private User $volunteer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->event = Event::factory()->create(['slug' => 'saraswati', 'title' => 'Saraswati Puja', 'chanda_box' => true, 'chanda_target' => 50000, 'starts_at' => now()->addMonths(2)]);
        $this->volunteer = $this->user(Role::Volunteer);
        $this->lane1 = ChandaArea::query()->create(['event_id' => $this->event->id, 'name' => 'Lane 1', 'collector_id' => $this->volunteer->id, 'target' => 10000]);
        $this->lane2 = ChandaArea::query()->create(['event_id' => $this->event->id, 'name' => 'Lane 2', 'target' => 10000]);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function gift(array $overrides = []): array
    {
        return ['donor_name' => 'Sample donor', 'area_id' => $this->lane1->id, 'amount' => 501, 'mode' => 'cash', ...$overrides];
    }

    public function test_a_volunteer_records_chanda_in_their_own_area(): void
    {
        $this->actingAs($this->volunteer)->get('/admin/chanda/saraswati')->assertOk()->assertSee('Lane 1')->assertDontSee('Lane 2');
        $this->actingAs($this->volunteer)->get('/admin/chanda/saraswati/receipts/create')->assertOk();

        $response = $this->actingAs($this->volunteer)->post('/admin/chanda/saraswati/receipts', $this->gift());
        $entry = ChandaEntry::query()->sole();
        $response->assertRedirect('/admin/chanda/receipts/'.$entry->id);

        $this->assertSame('CH-'.now()->format('y').'-0001', $entry->receipt_no);
        $this->assertSame($this->volunteer->id, $entry->collected_by);
        $this->actingAs($this->volunteer)->get('/admin/chanda/receipts/'.$entry->id)->assertOk()->assertSee($entry->receipt_no)->assertSee('Chanda receipt');
    }

    public function test_only_chanda_managers_see_the_drive_total_on_today(): void
    {
        app(ChandaBook::class)->record($this->event, ['donor_name' => 'Sample', 'amount' => 12345, 'mode' => ChandaMode::Cash, 'area' => $this->lane2], $this->user(Role::PujaInCharge));

        $this->actingAs($this->volunteer)->get('/admin')->assertOk()->assertDontSee('Saraswati Puja chanda')->assertDontSee('12,345');
        $this->actingAs($this->user(Role::PujaInCharge))->get('/admin')->assertOk()->assertSee('Saraswati Puja chanda');
    }

    public function test_a_volunteer_cannot_record_in_another_area_or_as_someone_else(): void
    {
        $this->actingAs($this->volunteer)->post('/admin/chanda/saraswati/receipts', $this->gift(['area_id' => $this->lane2->id]))->assertForbidden();
        $this->actingAs($this->volunteer)->post('/admin/chanda/saraswati/receipts', $this->gift(['area_id' => null]))->assertForbidden();

        $other = $this->user(Role::Volunteer);
        $this->actingAs($this->volunteer)->post('/admin/chanda/saraswati/receipts', $this->gift(['collected_by' => $other->id]));
        $this->assertSame($this->volunteer->id, ChandaEntry::query()->sole()->collected_by);

        // A volunteer does not see receipts others collected, nor the setup.
        $mine = ChandaEntry::query()->sole();
        $this->actingAs($other)->get('/admin/chanda/receipts/'.$mine->id)->assertForbidden();
        $this->actingAs($this->volunteer)->get('/admin/chanda/saraswati/budget')->assertForbidden();
        $this->actingAs($this->volunteer)->post('/admin/chanda/saraswati/areas', ['name' => 'Lane 9'])->assertForbidden();
        $this->actingAs($this->volunteer)->get('/admin/chanda/cash')->assertForbidden();
    }

    public function test_receipt_numbers_are_sequential_with_no_gaps(): void
    {
        $book = app(ChandaBook::class);
        $treasurer = $this->user(Role::Treasurer);

        $numbers = [];
        foreach ([101, 251, 501] as $amount) {
            $numbers[] = $book->record($this->event, ['donor_name' => 'Sample', 'amount' => $amount, 'mode' => ChandaMode::Cash], $treasurer)->receipt_no;
        }

        // A save that fails gives its number back.
        try {
            DB::transaction(function () use ($book, $treasurer): void {
                $book->record($this->event, ['donor_name' => 'Sample', 'amount' => 50, 'mode' => ChandaMode::Cash], $treasurer);
                throw new \RuntimeException('Power cut');
            });
        } catch (\RuntimeException) {
        }

        $numbers[] = $book->record($this->event, ['donor_name' => 'Sample', 'amount' => 1001, 'mode' => ChandaMode::Upi, 'upi_ref' => '631012345678'], $treasurer)->receipt_no;

        $year = now()->format('y');
        $this->assertSame(["CH-{$year}-0001", "CH-{$year}-0002", "CH-{$year}-0003", "CH-{$year}-0004"], $numbers);
        $this->assertSame([], $book->series((int) now()->format('Y'))['missing']);

        // Cancelling keeps the number in the series.
        $book->cancel(ChandaEntry::query()->where('receipt_no', "CH-{$year}-0002")->sole(), $treasurer, 'Wrong donor');
        $this->assertSame([], $book->series((int) now()->format('Y'))['missing']);
        $this->actingAs($treasurer)->get('/admin/chanda/saraswati/receipts')->assertOk()->assertSee('Series complete');
    }

    public function test_chanda_posts_to_the_club_book_once(): void
    {
        $book = app(ChandaBook::class);
        $entry = $book->record($this->event, ['donor_name' => 'Sample', 'amount' => 700, 'mode' => ChandaMode::Upi, 'upi_ref' => '631000000001'], $this->volunteer);

        $line = LedgerEntry::query()->sole();
        $this->assertSame(LedgerType::Income, $line->type);
        $this->assertSame(LedgerHead::PujaChanda, $line->head);
        $this->assertSame(LedgerAccount::Bank, $line->account);
        $this->assertSame(700, $line->amount);
        $this->assertSame($this->event->id, $line->event_id);
        $this->assertSame('club', $line->book->code->value);

        // Posting again returns the same line.
        $this->assertSame($line->id, $book->post($entry)->id);
        $this->assertSame($line->id, $book->post($entry->fresh() ?? $entry)->id);
        $this->assertSame(1, LedgerEntry::query()->count());

        // Cancelling reverses it.
        $book->cancel($entry, $this->user(Role::Treasurer), 'Bounced');
        $this->assertSame(0, app(Khata::class)->position()['income']);
        $this->expectException(ChandaException::class);
        $book->cancel($entry, $this->user(Role::Treasurer), 'Again');
    }

    public function test_sponsors_budget_and_spending(): void
    {
        $puja = $this->user(Role::PujaInCharge);

        $this->actingAs($puja)->post('/admin/chanda/saraswati/sponsors', ['name' => 'Sample Hardware', 'amount' => 10000, 'deliverable' => 'Gate banner'])->assertSessionHas('status');
        $sponsor = Sponsor::query()->sole();
        $this->assertSame(SponsorStatus::Promised, $sponsor->status);

        $this->actingAs($puja)->post("/admin/chanda/sponsors/{$sponsor->id}/receive", ['amount' => 10000, 'mode' => 'cash'])->assertRedirect();
        $this->assertTrue($sponsor->refresh()->isReceived());
        $this->assertSame(LedgerHead::Sponsorship, LedgerEntry::query()->sole()->head);
        $this->actingAs($puja)->post("/admin/chanda/sponsors/{$sponsor->id}/receive", ['amount' => 10000, 'mode' => 'cash'])->assertSessionHas('error');

        $this->actingAs($puja)->put('/admin/chanda/saraswati/budget', ['budget' => "Idol | প্রতিমা | 18000\nPandal | প্যান্ডেল | 24,000", 'chanda_target' => 60000, 'chanda_public' => '1'])
            ->assertRedirect('/admin/chanda/saraswati');
        $this->assertSame(42000, $this->event->refresh()->budgetTotal());
        $this->actingAs($puja)->put('/admin/chanda/saraswati/budget', ['budget' => 'No amount here'])->assertSessionHasErrors('budget');

        app(Khata::class)->record(Book::forCode(BookCode::Club), [
            'type' => LedgerType::Expense, 'head' => LedgerHead::PujaExpenses, 'account' => LedgerAccount::Cash,
            'amount' => 5000, 'entry_date' => now()->toDateString(), 'note' => 'Sample advance', 'event_id' => $this->event->id,
        ], $puja);

        $summary = app(ChandaBook::class)->summary($this->event->refresh());
        $this->assertSame(10000, $summary['sponsors']);
        $this->assertSame(5000, $summary['spent']);
        $this->assertSame(42000, $summary['budget']);

        $this->actingAs($puja)->get('/admin/chanda/saraswati')->assertOk()->assertSee('Sample Hardware');
        $this->actingAs($puja)->get('/admin/chanda/saraswati?lang=bn')->assertOk();
    }

    public function test_the_treasurer_receives_cash_from_a_collector(): void
    {
        $book = app(ChandaBook::class);
        foreach ([501, 251] as $amount) {
            $book->record($this->event, ['donor_name' => 'Sample', 'area' => $this->lane1, 'amount' => $amount, 'mode' => ChandaMode::Cash], $this->volunteer);
        }
        $book->record($this->event, ['donor_name' => 'Sample', 'area' => $this->lane1, 'amount' => 999, 'mode' => ChandaMode::Upi, 'upi_ref' => '631000000002'], $this->volunteer);

        $this->assertSame(752, $book->cashWithCollectors()->sole()->amount);
        $treasurer = $this->user(Role::Treasurer);
        $this->actingAs($treasurer)->get('/admin/chanda/cash')->assertOk()->assertSee($this->volunteer->name);

        $this->actingAs($treasurer)->post('/admin/chanda/cash', ['collector_id' => $this->volunteer->id, 'counted' => 700])->assertSessionHas('error');
        $this->actingAs($treasurer)->post('/admin/chanda/cash', ['collector_id' => $this->volunteer->id, 'counted' => 700, 'note' => 'Short by 52, coins with Bapi'])->assertSessionHas('status');

        $this->assertTrue($book->cashWithCollectors()->isEmpty());
        $this->assertSame(2, ChandaEntry::query()->whereNotNull('handover_id')->count());
        // The Puja in-charge does not receive cash.
        $this->actingAs($this->user(Role::PujaInCharge))->post('/admin/chanda/cash', ['collector_id' => $this->volunteer->id, 'counted' => 0])->assertForbidden();
    }

    public function test_the_website_shows_totals_only_when_published_and_names_only_with_consent(): void
    {
        SiteSetting::query()->updateOrCreate(['key' => 'chanda'], ['value' => ['event_slug' => 'saraswati']]);
        $book = app(ChandaBook::class);
        $book->record($this->event, ['donor_name' => 'Sample Named Donor', 'area' => $this->lane1, 'amount' => 501, 'mode' => ChandaMode::Cash, 'show_name' => true], $this->volunteer);
        $book->record($this->event, ['donor_name' => 'Sample Quiet Donor', 'area' => $this->lane1, 'amount' => 1001, 'mode' => ChandaMode::Cash], $this->volunteer);

        $this->get('/chanda')->assertOk()->assertDontSee('Lane by lane')->assertDontSee('Sample Named Donor');

        $this->event->update(['chanda_public' => true]);
        $this->get('/chanda')->assertOk()->assertSee('Lane by lane')->assertSee('Sample Named Donor')->assertDontSee('Sample Quiet Donor');
    }

    public function test_roles_without_chanda_are_kept_out(): void
    {
        foreach ([Role::WebsiteEditor, Role::NurseryInCharge, Role::CateringInCharge] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get('/admin/chanda/saraswati')->assertForbidden();
            $this->actingAs($user)->post('/admin/chanda/saraswati/receipts', $this->gift())->assertForbidden();
        }

        $this->assertSame(0, ChandaEntry::query()->count());
    }

    public function test_the_khata_entry_form_can_mark_an_expense_for_an_event(): void
    {
        $treasurer = $this->user(Role::Treasurer);
        $this->actingAs($treasurer)->get('/admin/khata/create')->assertOk()->assertSee('For event (optional)');

        $this->actingAs($treasurer)->post('/admin/khata', [
            'book' => 'club', 'type' => 'expense', 'head' => 'puja_expenses', 'account' => 'cash', 'amount' => 1200,
            'entry_date' => now()->toDateString(), 'note' => 'Sample flowers', 'event_id' => $this->event->id,
        ])->assertRedirect();

        $this->assertSame($this->event->id, LedgerEntry::query()->sole()->event_id);
    }
}
