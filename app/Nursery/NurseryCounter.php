<?php

namespace App\Nursery;

use App\Enums\BookCode;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentMode;
use App\Enums\StockMoveKind;
use App\Models\Book;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\NurserySale;
use App\Models\NurserySaleItem;
use App\Models\Plant;
use App\Models\StockMove;
use App\Models\User;
use App\Payments\Khata;
use App\Support\ReceiptNumbers;
use Illuminate\Support\Facades\DB;

/**
 * The nursery counter and its stock:
 * - a sale locks the plants it sells, checks the stock, charges the member price to a club family,
 *   takes the next NS receipt and puts the money in the Nursery book;
 * - a cancelled sale keeps its number, puts the plants back and reverses the income;
 * - stock in (with what the plants cost, an expense in the Nursery book) and losses.
 *
 * Every change of stock is a stock move, so plants.stock is always the sum of its moves.
 */
class NurseryCounter
{
    /** Most plants of one kind in one sale or delivery. */
    public const MAX_QTY = 500;

    public function __construct(private readonly ReceiptNumbers $numbers, private readonly Khata $khata) {}

    /**
     * @param  array<int, int>  $lines  plant id => quantity
     *
     * @throws NurseryException
     */
    public function sell(array $lines, ?Family $family, ?string $buyerName, PaymentMode $mode, ?string $upiRef, User $by): NurserySale
    {
        $lines = array_filter($lines, fn (int $qty): bool => $qty > 0);

        if ($lines === []) {
            throw new NurseryException('Add at least one plant to the bill.');
        }

        return DB::transaction(function () use ($lines, $family, $buyerName, $mode, $upiRef, $by): NurserySale {
            // Lock in id order so two counters selling the same plants never deadlock.
            $plants = Plant::query()->whereKey(array_keys($lines))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $total = 0;
            foreach ($lines as $id => $qty) {
                $plant = $plants->get($id);

                if ($plant === null || ! $plant->active) {
                    throw new NurseryException('A plant on the bill is no longer sold.');
                }
                if ($plant->stock < $qty) {
                    throw new NurseryException('Only :count of :plant left in stock.', ['count' => $plant->stock, 'plant' => $plant->tr('name')]);
                }

                $subtotal += $plant->price * $qty;
                $total += $this->priceFor($plant, $family !== null) * $qty;
            }

            $sale = NurserySale::query()->create([
                'buyer_name' => $family !== null ? $family->head_name : $buyerName,
                'family_id' => $family?->id,
                'subtotal' => $subtotal,
                'discount' => $subtotal - $total,
                'total' => $total,
                'mode' => $mode,
                'upi_ref' => $mode === PaymentMode::Upi ? $upiRef : null,
                'receipt_no' => $this->numbers->next(BookCode::Nursery),
                'recorded_by' => $by->id,
            ]);

            foreach ($lines as $id => $qty) {
                $plant = $plants->get($id);
                assert($plant instanceof Plant);

                NurserySaleItem::query()->create(['sale_id' => $sale->id, 'plant_id' => $plant->id, 'qty' => $qty, 'rate' => $this->priceFor($plant, $family !== null)]);
                $this->move($plant, -$qty, StockMoveKind::Sale, $sale->receipt_no, $by, saleId: $sale->id);
            }

            $line = new LedgerEntry([
                'book_id' => Book::forCode(BookCode::Nursery)->id,
                'entry_date' => now()->toDateString(),
                'type' => LedgerType::Income,
                'account' => $mode->account(),
                'head' => LedgerHead::NurserySales,
                'amount' => $total,
                'note' => mb_substr("{$sale->receipt_no} · ".($sale->buyer_name ?: 'Counter sale'), 0, 255),
                'recorded_by' => $by->id,
            ]);
            $line->source()->associate($sale);
            $line->save();
            $sale->forceFill(['ledger_entry_id' => $line->id])->save();

            activity('nursery')->performedOn($sale)->causedBy($by)
                ->withProperties(['receipt' => $sale->receipt_no, 'total' => $total, 'mode' => $mode->value, 'items' => $lines])
                ->event('created')
                ->log("Nursery sale {$sale->receipt_no}: ₹{$total}");

            return $sale;
        });
    }

    /**
     * Cancel a sale made by mistake: the number stays, the plants go back, the income is reversed.
     *
     * @throws NurseryException
     */
    public function cancel(NurserySale $sale, string $reason, User $by): NurserySale
    {
        return DB::transaction(function () use ($sale, $reason, $by): NurserySale {
            $locked = NurserySale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw new NurseryException('This sale is already cancelled.');
            }

            $items = $locked->items()->orderBy('plant_id')->get();
            $plants = Plant::query()->whereKey($items->pluck('plant_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $plant = $plants->get($item->plant_id);
                assert($plant instanceof Plant);
                $this->move($plant, $item->qty, StockMoveKind::Adjust, "Cancelled {$locked->receipt_no}", $by, saleId: $locked->id);
            }

            if ($locked->ledger_entry_id !== null) {
                $this->khata->reverse(LedgerEntry::query()->findOrFail($locked->ledger_entry_id), $by, "Sale {$locked->receipt_no} cancelled: {$reason}");
            }

            $locked->forceFill(['cancelled_at' => now(), 'cancelled_by' => $by->id, 'cancel_reason' => mb_substr($reason, 0, 200)])->save();

            activity('nursery')->performedOn($locked)->causedBy($by)
                ->withProperties(['receipt' => $locked->receipt_no, 'reason' => $reason])
                ->event('cancelled')
                ->log("Nursery sale {$locked->receipt_no} cancelled: {$reason}");

            $sale->setRawAttributes($locked->getAttributes(), true);

            return $sale;
        });
    }

    /**
     * Plants delivered or raised. If they cost money, the cost is an expense in the Nursery book.
     *
     * @throws NurseryException
     */
    public function stockIn(Plant $plant, int $qty, ?int $costEach, LedgerAccount $account, ?string $from, User $by): StockMove
    {
        if ($qty < 1 || $qty > self::MAX_QTY) {
            throw new NurseryException('Enter how many plants came in.');
        }

        return DB::transaction(function () use ($plant, $qty, $costEach, $account, $from, $by): StockMove {
            $locked = Plant::query()->whereKey($plant->id)->lockForUpdate()->firstOrFail();
            $move = $this->move($locked, $qty, StockMoveKind::In, $from, $by, cost: $costEach);

            if ($costEach !== null && $costEach > 0) {
                $this->khata->record(Book::forCode(BookCode::Nursery), [
                    'type' => LedgerType::Expense,
                    'head' => LedgerHead::PlantsAndPots,
                    'account' => $account,
                    'amount' => $costEach * $qty,
                    'entry_date' => now()->toDateString(),
                    'note' => mb_substr("{$locked->name} × {$qty}".($from ? " · {$from}" : ''), 0, 255),
                ], $by, source: $move);
            }

            $plant->setRawAttributes($locked->getAttributes(), true);

            return $move;
        });
    }

    /**
     * Plants that died, broke or were given away.
     *
     * @throws NurseryException
     */
    public function loss(Plant $plant, int $qty, string $reason, User $by): StockMove
    {
        return DB::transaction(function () use ($plant, $qty, $reason, $by): StockMove {
            $locked = Plant::query()->whereKey($plant->id)->lockForUpdate()->firstOrFail();

            if ($qty < 1 || $qty > $locked->stock) {
                throw new NurseryException('Only :count of :plant left in stock.', ['count' => $locked->stock, 'plant' => $locked->tr('name')]);
            }

            $move = $this->move($locked, -$qty, StockMoveKind::Loss, $reason, $by);
            $plant->setRawAttributes($locked->getAttributes(), true);

            return $move;
        });
    }

    public function priceFor(Plant $plant, bool $member): int
    {
        return $member ? min($plant->member_price, $plant->price) : $plant->price;
    }

    private function move(Plant $plant, int $qty, StockMoveKind $kind, ?string $ref, User $by, ?int $cost = null, ?int $saleId = null): StockMove
    {
        $plant->forceFill(['stock' => $plant->stock + $qty])->save();

        $move = StockMove::query()->create([
            'plant_id' => $plant->id,
            'qty' => $qty,
            'kind' => $kind,
            'cost' => $cost,
            'sale_id' => $saleId,
            'ref' => $ref !== null ? mb_substr($ref, 0, 255) : null,
            'recorded_by' => $by->id,
        ]);

        if ($kind !== StockMoveKind::Sale) {
            activity('nursery')->performedOn($plant)->causedBy($by)
                ->withProperties(['kind' => $kind->value, 'qty' => $qty, 'stock' => $plant->stock])
                ->event('stock')
                ->log("Stock {$kind->englishLabel()}: {$plant->name} {$qty}, now {$plant->stock}");
        }

        return $move;
    }
}
