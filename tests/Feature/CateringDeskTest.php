<?php

namespace Tests\Feature;

use App\Catering\CateringDesk;
use App\Enums\BookCode;
use App\Enums\CateringOrderStatus;
use App\Enums\EnquiryKind;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\Role;
use App\Models\Book;
use App\Models\CateringOrder;
use App\Models\CateringPackage;
use App\Models\CateringPayment;
use App\Models\Enquiry;
use App\Models\LedgerEntry;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CateringDeskTest extends TestCase
{
    use RefreshDatabase;

    private User $incharge;

    private CateringPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->incharge = $this->user(Role::CateringInCharge);
        $this->package = CateringPackage::factory()->create(['name' => 'Standard serving', 'rate' => 100]);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function enquiry(array $overrides = []): CateringOrder
    {
        $this->actingAs($this->incharge)->post('/admin/catering', [
            'customer_name' => 'Sample Customer',
            'phone' => '98300 00001',
            'occasion' => 'wedding',
            'event_date' => now()->addDays(20)->toDateString(),
            'guests' => 120,
            'package_id' => $this->package->id,
            ...$overrides,
        ])->assertRedirect();

        return CateringOrder::query()->latest('id')->firstOrFail();
    }

    private function cateringIncome(): int
    {
        return (int) LedgerEntry::query()->where('book_id', Book::forCode(BookCode::Catering)->id)
            ->where('type', LedgerType::Income->value)->sum(\DB::raw(LedgerEntry::SIGNED_AMOUNT));
    }

    public function test_an_order_goes_from_enquiry_to_settled_with_receipts_in_the_catering_book(): void
    {
        $order = $this->enquiry();
        $this->assertSame(CateringOrderStatus::Enquiry, $order->status);
        $this->actingAs($this->incharge)->get('/admin/catering?order='.$order->id)->assertOk()->assertSee('Sample Customer')->assertSee('Send quotation');

        // Quote: 120 guests at ₹100.
        $this->actingAs($this->incharge)->put("/admin/catering/{$order->id}/quote", ['guests' => 120, 'package_id' => $this->package->id, 'note' => 'Sample note'])->assertRedirect();
        $order->refresh();
        $this->assertSame(CateringOrderStatus::Quoted, $order->status);
        $this->assertSame(12000, $order->total());
        $this->assertSame(2400, $order->advanceDue());

        // Advance confirms the booking and gets a CT receipt.
        $response = $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/payments", ['kind' => 'advance', 'amount' => 2400, 'mode' => 'cash']);
        $advance = CateringPayment::query()->sole();
        $response->assertRedirect(route('admin.catering.receipt', $advance));
        $this->assertMatchesRegularExpression('/^CT-\d{2}-0001$/', (string) $advance->receipt_no);
        $this->assertSame(CateringOrderStatus::Advance, $order->refresh()->status);
        $this->actingAs($this->incharge)->get(route('admin.catering.receipt', $advance))->assertOk()->assertSee((string) $advance->receipt_no);

        // Served with 10 extra guests: the bill grows at the same rate.
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/served", ['guests' => 130])->assertRedirect();
        $order->refresh();
        $this->assertSame(CateringOrderStatus::Done, $order->status);
        $this->assertSame(13000, $order->total());

        // Paying more than the balance is refused.
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/payments", ['kind' => 'final', 'amount' => 20000, 'mode' => 'cash'])->assertSessionHas('error');

        // The final bill clears the balance and settles the order.
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/payments", ['kind' => 'final', 'amount' => 10600, 'mode' => 'upi', 'upi_ref' => '631000000042'])->assertRedirect();
        $order->refresh();
        $this->assertSame(CateringOrderStatus::Settled, $order->status);
        $this->assertNotNull($order->settled_at);
        $this->assertSame(13000, $this->cateringIncome());
        $this->assertSame(2, LedgerEntry::query()->where('head', LedgerHead::CateringIncome->value)->count());
    }

    public function test_member_rate_is_fixed_at_the_quote(): void
    {
        $order = $this->enquiry(['is_member' => '1']);
        $this->assertSame(90, $order->rate);

        $this->package->update(['rate' => 200]);
        $this->assertSame(90, $order->refresh()->rate);
    }

    public function test_serving_team_wages_and_expenses_make_the_profit(): void
    {
        $order = $this->enquiry(['guests' => 30]);
        $this->actingAs($this->incharge)->put("/admin/catering/{$order->id}/quote", ['guests' => 30, 'package_id' => $this->package->id]);
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/payments", ['kind' => 'advance', 'amount' => 600, 'mode' => 'cash']);

        foreach (['Bapi Sample', 'Rahul Sample', 'Tumpa Sample'] as $i => $name) {
            $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/team", ['server_name' => $name, 'wage' => 500, 'is_leader' => $i === 0 ? '1' : '0'])->assertRedirect();
        }
        $this->assertSame(1, $order->roster()->where('is_leader', true)->count());

        // Wages only after serving.
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/wages", ['account' => 'cash'])->assertSessionHas('error');

        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/served", ['guests' => 30]);
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/wages", ['account' => 'cash'])->assertSessionHas('status');
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/wages", ['account' => 'cash'])->assertSessionHas('error');
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/expense", [])->assertNotFound();
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/expenses", ['head' => 'other_expense', 'amount' => 250, 'account' => 'cash', 'note' => 'Plates'])->assertSessionHas('status');

        $summary = app(CateringDesk::class)->summary($order->refresh());
        $this->assertSame(1750, $summary['expenses']);
        $this->assertSame(600 - 1750, $summary['profit']);
        $this->assertSame(1500, (int) LedgerEntry::query()->where('head', LedgerHead::Wages->value)->value('amount'));

        // The team is fixed once wages are paid.
        $this->actingAs($this->incharge)->post("/admin/catering/{$order->id}/team", ['server_name' => 'Late Sample', 'wage' => 500])->assertSessionHas('error');
    }

    public function test_a_quote_can_be_lost_and_a_booking_cancelled_with_a_refund(): void
    {
        $lost = $this->enquiry();
        $this->actingAs($this->incharge)->post("/admin/catering/{$lost->id}/lost", ['reason' => 'price'])->assertSessionHas('status');
        $lost->refresh();
        $this->assertSame(CateringOrderStatus::Lost, $lost->status);
        $this->assertSame('Price too high', $lost->closed_reason);

        $booked = $this->enquiry();
        $this->actingAs($this->incharge)->put("/admin/catering/{$booked->id}/quote", ['guests' => 120, 'package_id' => $this->package->id]);
        $this->actingAs($this->incharge)->post("/admin/catering/{$booked->id}/payments", ['kind' => 'advance', 'amount' => 2400, 'mode' => 'cash']);

        // Can't lose a booking that took money; can't refund more than was paid.
        $this->actingAs($this->incharge)->post("/admin/catering/{$booked->id}/lost", ['reason' => 'price'])->assertSessionHas('error');
        $this->actingAs($this->incharge)->post("/admin/catering/{$booked->id}/cancel", ['refund' => 5000, 'mode' => 'cash', 'reason' => 'Called off'])->assertSessionHas('error');

        $this->actingAs($this->incharge)->post("/admin/catering/{$booked->id}/cancel", ['refund' => 1200, 'mode' => 'cash', 'reason' => 'Called off'])->assertSessionHas('status');
        $this->assertSame(CateringOrderStatus::Cancelled, $booked->refresh()->status);
        $refund = LedgerEntry::query()->where('head', LedgerHead::Refunds->value)->sole();
        $this->assertSame(LedgerType::Expense, $refund->type);
        $this->assertSame(1200, $refund->amount);

        $this->actingAs($this->incharge)->get('/admin/catering?show=closed')->assertOk()->assertSee('Price too high');
    }

    public function test_a_website_enquiry_is_put_on_the_board_and_marked_handled(): void
    {
        $enquiry = Enquiry::factory()->create([
            'kind' => EnquiryKind::Catering,
            'name' => 'Website Sample',
            'phone' => '98300 00002',
            'payload' => ['occasion' => 'birthday', 'event_date' => now()->addDays(9)->toDateString(), 'guests' => 60, 'package' => $this->package->slug, 'is_member' => true],
        ]);

        $this->actingAs($this->incharge)->get('/admin/catering')->assertSee('Website Sample')->assertSee('Put on the board');
        $this->actingAs($this->incharge)->get('/admin/catering/create?enquiry='.$enquiry->id)->assertOk()->assertSee('value="60"', false);

        $order = $this->enquiry(['customer_name' => 'Website Sample', 'guests' => 60, 'enquiry_id' => $enquiry->id, 'is_member' => '1']);
        $this->assertSame($enquiry->id, $order->enquiry_id);
        $this->assertNotNull($enquiry->refresh()->handled_at);
    }

    public function test_only_the_catering_in_charge_and_admin_run_orders(): void
    {
        foreach ([Role::NurseryInCharge, Role::Volunteer, Role::WebsiteEditor, Role::Treasurer] as $role) {
            $this->actingAs($this->user($role))->get('/admin/catering')->assertForbidden();
        }
        $this->actingAs($this->user(Role::Admin))->get('/admin/catering')->assertOk();
        $this->actingAs($this->incharge)->get('/admin/catering')->assertOk();
    }
}
