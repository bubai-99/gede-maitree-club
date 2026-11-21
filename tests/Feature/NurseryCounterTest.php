<?php

namespace Tests\Feature;

use App\Enums\BookCode;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\Role;
use App\Enums\StockMoveKind;
use App\Models\Book;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\NurserySale;
use App\Models\Plant;
use App\Models\StockMove;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NurseryCounterTest extends TestCase
{
    use RefreshDatabase;

    private User $incharge;

    private Plant $tulsi;

    private Plant $hibiscus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        $this->incharge = $this->user(Role::NurseryInCharge);
        $this->tulsi = Plant::factory()->create(['name' => 'Tulsi', 'price' => 60, 'member_price' => 54, 'stock' => 10]);
        $this->hibiscus = Plant::factory()->create(['name' => 'Hibiscus', 'price' => 150, 'member_price' => 135, 'stock' => 3]);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->committee()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function nurseryBalance(): int
    {
        return (int) LedgerEntry::query()->where('book_id', Book::forCode(BookCode::Nursery)->id)
            ->where('type', LedgerType::Income->value)->sum(DB::raw(LedgerEntry::SIGNED_AMOUNT));
    }

    public function test_a_counter_sale_takes_stock_and_goes_into_the_nursery_book(): void
    {
        $this->actingAs($this->incharge)->get('/admin/nursery')->assertOk()->assertSee('Tulsi')->assertSee('Hibiscus');

        $this->actingAs($this->incharge)->post('/admin/nursery/sales', [
            'lines' => [$this->tulsi->id => 2, $this->hibiscus->id => 1],
            'mode' => 'cash',
        ])->assertRedirect('/admin/nursery')->assertSessionHas('status');

        $sale = NurserySale::query()->sole();
        $this->assertMatchesRegularExpression('/^NS-\d{2}-0001$/', (string) $sale->receipt_no);
        $this->assertSame(270, $sale->total);
        $this->assertSame(0, $sale->discount);
        $this->assertSame(8, $this->tulsi->refresh()->stock);
        $this->assertSame(2, $this->hibiscus->refresh()->stock);
        $this->assertSame(270, $this->nurseryBalance());
        $this->assertSame(-2, (int) StockMove::query()->where('plant_id', $this->tulsi->id)->where('kind', StockMoveKind::Sale->value)->sum('qty'));

        $this->actingAs($this->incharge)->get(route('admin.nursery.sale', $sale))->assertOk()->assertSee((string) $sale->receipt_no);
    }

    public function test_a_member_family_pays_the_member_price(): void
    {
        Family::factory()->create(['family_no' => 'MC-0104', 'head_name' => 'Sample Head']);

        $this->actingAs($this->incharge)->post('/admin/nursery/sales', ['lines' => [$this->tulsi->id => 3], 'family_no' => '104', 'mode' => 'upi', 'upi_ref' => '631000000077'])->assertRedirect();

        $sale = NurserySale::query()->sole();
        $this->assertSame(162, $sale->total);
        $this->assertSame(18, $sale->discount);
        $this->assertSame('Sample Head', $sale->buyer_name);

        $this->actingAs($this->incharge)->post('/admin/nursery/sales', ['lines' => [$this->tulsi->id => 1], 'family_no' => 'MC-9999', 'mode' => 'cash'])->assertSessionHasErrors('family_no');
    }

    public function test_selling_more_than_the_stock_is_refused(): void
    {
        $this->actingAs($this->incharge)->post('/admin/nursery/sales', ['lines' => [$this->hibiscus->id => 4], 'mode' => 'cash'])->assertSessionHas('error');
        $this->actingAs($this->incharge)->post('/admin/nursery/sales', ['lines' => [$this->hibiscus->id => 0], 'mode' => 'cash'])->assertSessionHas('error');
        $this->assertSame(0, NurserySale::query()->count());
        $this->assertSame(3, $this->hibiscus->refresh()->stock);
    }

    public function test_cancelling_a_sale_returns_the_plants_and_reverses_the_income(): void
    {
        $this->actingAs($this->incharge)->post('/admin/nursery/sales', ['lines' => [$this->tulsi->id => 4], 'mode' => 'cash']);
        $sale = NurserySale::query()->sole();

        $this->actingAs($this->incharge)->post(route('admin.nursery.cancel', $sale), ['reason' => 'Wrong plant'])->assertSessionHas('status');
        $this->assertTrue($sale->refresh()->isCancelled());
        $this->assertSame(10, $this->tulsi->refresh()->stock);
        $this->assertSame(0, $this->nurseryBalance());

        $this->actingAs($this->incharge)->post(route('admin.nursery.cancel', $sale), ['reason' => 'Again'])->assertSessionHas('error');
    }

    public function test_stock_in_with_a_cost_and_a_loss(): void
    {
        $this->actingAs($this->incharge)->post(route('admin.nursery.stock.in', $this->tulsi), ['qty' => 20, 'cost' => 25, 'account' => 'cash', 'from' => 'Sample wholesale'])->assertSessionHas('status');
        $this->assertSame(30, $this->tulsi->refresh()->stock);
        $cost = LedgerEntry::query()->where('head', LedgerHead::PlantsAndPots->value)->sole();
        $this->assertSame(500, $cost->amount);
        $this->assertSame(StockMove::class, $cost->source_type);

        $this->actingAs($this->incharge)->post(route('admin.nursery.stock.in', $this->hibiscus), ['qty' => 5, 'account' => 'cash'])->assertSessionHas('status');
        $this->assertSame(1, LedgerEntry::query()->count());

        $this->actingAs($this->incharge)->post(route('admin.nursery.stock.loss', $this->tulsi), ['qty' => 2, 'reason' => 'died'])->assertSessionHas('status');
        $this->assertSame(28, $this->tulsi->refresh()->stock);
        $this->actingAs($this->incharge)->post(route('admin.nursery.stock.loss', $this->tulsi), ['qty' => 99, 'reason' => 'died'])->assertSessionHas('error');

        $this->actingAs($this->incharge)->get('/admin/nursery/stock')->assertOk()->assertSee('Died');

        // The stock column always equals the sum of the moves (factory stock was not a move).
        $this->assertSame(18, (int) StockMove::query()->where('plant_id', $this->tulsi->id)->sum('qty'));
    }

    public function test_only_the_nursery_in_charge_and_admin_use_the_counter(): void
    {
        foreach ([Role::CateringInCharge, Role::Volunteer, Role::WebsiteEditor, Role::Treasurer] as $role) {
            $this->actingAs($this->user($role))->get('/admin/nursery')->assertForbidden();
        }
        $this->actingAs($this->user(Role::Admin))->get('/admin/nursery')->assertOk();
    }
}
