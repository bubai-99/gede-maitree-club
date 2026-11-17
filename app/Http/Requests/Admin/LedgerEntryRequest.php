<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookCode;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Models\Book;
use App\Models\LedgerEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A hand-written khata entry: book, joma or khoroch (or cash to/from the bank), head,
 * amount in whole rupees, date, note and an optional photo of the bill.
 */
class LedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $book = $this->book();

        return $book !== null && ($this->user()?->can('createIn', [LedgerEntry::class, $book]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = LedgerType::tryFrom($this->string('type')->toString());
        $heads = $type !== null ? array_map(fn (LedgerHead $head): string => $head->value, LedgerHead::forType($type)) : [];

        return [
            'book' => ['required', Rule::enum(BookCode::class)],
            'type' => ['required', Rule::enum(LedgerType::class)],
            'head' => [Rule::requiredIf($type !== null && ! $type->isTransfer()), 'nullable', Rule::in($heads)],
            'account' => ['required', Rule::enum(LedgerAccount::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'entry_date' => ['required', 'date', 'before_or_equal:today', 'after:2014-01-01'],
            'note' => ['nullable', 'string', 'max:255'],
            'bill' => ['nullable', 'file', 'mimetypes:'.implode(',', LedgerEntry::BILL_MIMES), 'max:4096'],
        ];
    }

    public function book(): ?Book
    {
        $code = BookCode::tryFrom($this->string('book')->toString());

        return $code !== null ? Book::forCode($code) : null;
    }

    /**
     * @return array{type: LedgerType, head: LedgerHead, account: LedgerAccount, amount: int, entry_date: string, note: ?string}
     */
    public function entryData(): array
    {
        $type = LedgerType::from($this->string('type')->toString());

        return [
            'type' => $type,
            'head' => $type->isTransfer() ? LedgerHead::CashTransfer : LedgerHead::from($this->string('head')->toString()),
            'account' => LedgerAccount::from($this->string('account')->toString()),
            'amount' => $this->integer('amount'),
            'entry_date' => $this->string('entry_date')->toString(),
            'note' => $this->filled('note') ? $this->string('note')->toString() : null,
        ];
    }
}
