<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerAccount;
use App\Enums\PaymentMode;
use App\Enums\StockMoveKind;
use App\Http\Controllers\Controller;
use App\Models\NurserySale;
use App\Models\Plant;
use App\Models\StockMove;
use App\Models\User;
use App\Nursery\NurseryCounter;
use App\Nursery\NurseryException;
use App\Passes\GateKeeper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console → Nursery: the counter (tap plants, member price for a family, cash or UPI, an NS
 * receipt), the day's sales, and the stock list with stock in and losses.
 * Nursery in-charge and Admin (route middleware can:nursery.manage). The catalogue itself
 * (names, photos, prices on the website) is edited under Website → Plants.
 */
class NurseryController extends Controller
{
    public function __construct(private readonly NurseryCounter $counter) {}

    public function index(): View
    {
        return view('admin.nursery.index', [
            'plants' => Plant::query()->where('active', true)->orderBy('sort')->orderBy('name')->get(),
            'sales' => NurserySale::query()->whereDate('created_at', today())->with('family')->latest('id')->get(),
            'discount' => (int) config('club.member_discount_percent'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:60'],
            'lines.*' => ['integer', 'min:0', 'max:'.NurseryCounter::MAX_QTY],
            'family_no' => ['nullable', 'string', 'max:20'],
            'buyer_name' => ['nullable', 'string', 'max:120'],
            'mode' => ['required', Rule::in([PaymentMode::Cash->value, PaymentMode::Upi->value])],
            'upi_ref' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{6,40}$/'],
        ]);

        $family = null;
        if (filled($data['family_no'] ?? null)) {
            $family = GateKeeper::findFamily((string) $data['family_no']);
            if ($family === null) {
                return back()->withInput()->withErrors(['family_no' => __('No family with this number')]);
            }
        }

        /** @var array<int|string, int|string> $raw */
        $raw = $data['lines'];
        $lines = [];
        foreach ($raw as $id => $qty) {
            $lines[(int) $id] = (int) $qty;
        }

        try {
            $sale = $this->counter->sell($lines, $family, $data['buyer_name'] ?? null, PaymentMode::from($data['mode']), $data['upi_ref'] ?? null, $this->user($request));
        } catch (NurseryException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.nursery.index')
            ->with('status', 'Sale :receipt saved in the Nursery book. Stock updated.')
            ->with('status_replace', ['receipt' => (string) $sale->receipt_no])
            ->with('sale_id', $sale->id);
    }

    public function sale(NurserySale $sale): View
    {
        $sale->load(['items.plant', 'family', 'recorder']);

        return view('admin.nursery.sale', ['sale' => $sale]);
    }

    public function cancel(Request $request, NurserySale $sale): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:200']]);

        try {
            $this->counter->cancel($sale, $data['reason'], $this->user($request));
        } catch (NurseryException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.nursery.sale', $sale)->with('status', 'Sale cancelled. The plants are back in stock and the khata has a reversing entry.');
    }

    public function stock(): View
    {
        $sold = StockMove::query()
            ->where('kind', StockMoveKind::Sale->value)
            ->where('created_at', '>=', now()->startOfMonth())
            ->groupBy('plant_id')
            ->select('plant_id', DB::raw('-SUM(qty) AS sold'))
            ->toBase()->pluck('sold', 'plant_id');

        return view('admin.nursery.stock', [
            'plants' => Plant::query()->orderBy('sort')->orderBy('name')->get(),
            'sold' => $sold->map(fn ($n): int => (int) $n)->all(),
            'moves' => StockMove::query()->where('kind', '!=', StockMoveKind::Sale->value)->with(['plant', 'recorder'])->latest('id')->limit(15)->get(),
        ]);
    }

    public function stockIn(Request $request, Plant $plant): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.NurseryCounter::MAX_QTY],
            'cost' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'account' => ['required', Rule::enum(LedgerAccount::class)],
            'from' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->counter->stockIn($plant, (int) $data['qty'], isset($data['cost']) ? (int) $data['cost'] : null, LedgerAccount::from($data['account']), $data['from'] ?? null, $this->user($request));
        } catch (NurseryException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.nursery.stock')->with('status', 'Stock updated. Any cost is entered in the Nursery book.');
    }

    public function loss(Request $request, Plant $plant): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.NurseryCounter::MAX_QTY],
            'reason' => ['required', Rule::in(array_keys(self::lossReasons()))],
        ]);

        try {
            $this->counter->loss($plant, (int) $data['qty'], self::lossReasons()[$data['reason']]['label'], $this->user($request));
        } catch (NurseryException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.nursery.stock')->with('status', 'Loss recorded. Stock reduced.');
    }

    /**
     * Why plants left the stock (key => English label, translated where shown; English is stored).
     *
     * @return array<string, array{label: string}>
     */
    public static function lossReasons(): array
    {
        return [
            'died' => ['label' => 'Died'],
            'damaged' => ['label' => 'Damaged'],
            'free' => ['label' => 'Given free'],
            'count' => ['label' => 'Count correction'],
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
