<?php

namespace App\Models;

use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A line in one of the books (khata). Entries are never edited or deleted: a mistake is
 * put right with a reversing entry (same book, type and head, reverses_id set), which
 * counts as minus its amount in every total.
 */
#[Fillable(['book_id', 'event_id', 'entry_date', 'type', 'account', 'head', 'amount', 'note', 'source_type', 'source_id', 'recorded_by', 'reverses_id'])]
class LedgerEntry extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** Bill photos the khata accepts. */
    public const BILL_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * The amount with its sign: a reversal counts as minus.
     */
    public const SIGNED_AMOUNT = 'CASE WHEN reverses_id IS NULL THEN amount ELSE -amount END';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'type' => LedgerType::class,
            'account' => LedgerAccount::class,
            'head' => LedgerHead::class,
            'amount' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        // Bills stay on the private disk and are shown through the console (policy checked).
        $this->addMediaCollection('bill')
            ->useDisk('local')
            ->singleFile()
            ->acceptsMimeTypes(self::BILL_MIMES);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * The event this line belongs to (puja chanda, sponsorship, puja expenses), if any.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * The entry this one reverses.
     *
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'reverses_id');
    }

    /**
     * The entry that reverses this one, if any.
     *
     * @return HasOne<LedgerEntry, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(LedgerEntry::class, 'reverses_id');
    }

    /**
     * Rupees with sign: negative for a reversal.
     */
    public function signedAmount(): int
    {
        return $this->reverses_id === null ? $this->amount : -$this->amount;
    }

    /**
     * Change to the book's balance (income +, expense −, transfers 0).
     */
    public function balanceEffect(): int
    {
        return match ($this->type) {
            LedgerType::Income => $this->signedAmount(),
            LedgerType::Expense => -$this->signedAmount(),
            default => 0,
        };
    }

    /**
     * Change to cash in hand.
     */
    public function cashEffect(): int
    {
        return match ($this->type) {
            LedgerType::Income => $this->account === LedgerAccount::Cash ? $this->signedAmount() : 0,
            LedgerType::Expense => $this->account === LedgerAccount::Cash ? -$this->signedAmount() : 0,
            LedgerType::Deposit => -$this->signedAmount(),
            LedgerType::Withdrawal => $this->signedAmount(),
        };
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInBook(Builder $query, Book $book): void
    {
        $query->where('book_id', $book->id);
    }
}
