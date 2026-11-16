<?php

namespace Tests\Feature;

use App\Enums\BookCode;
use App\Support\ReceiptNumbers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceiptNumbersTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_are_sequential_per_book_and_year(): void
    {
        $numbers = app(ReceiptNumbers::class);
        $date = Carbon::parse('2026-11-17');

        $this->assertSame('MC-26-0001', $numbers->next(BookCode::Club, $date));
        $this->assertSame('MC-26-0002', $numbers->next(BookCode::Club, $date));
        $this->assertSame('CT-26-0001', $numbers->next(BookCode::Catering, $date));
        $this->assertSame('NS-26-0001', $numbers->next(BookCode::Nursery, $date));
        $this->assertSame('MC-27-0001', $numbers->next(BookCode::Club, Carbon::parse('2027-01-02')));
        $this->assertSame('MC-26-0003', $numbers->next(BookCode::Club, $date));
    }

    public function test_many_numbers_are_all_different(): void
    {
        $numbers = app(ReceiptNumbers::class);

        $issued = collect(range(1, 60))->map(fn () => $numbers->next(BookCode::Club));

        $this->assertCount(60, $issued->unique());
        $this->assertSame(60, (int) DB::table('receipt_counters')->where('book_code', 'club')->value('last_no'));
    }

    public function test_the_counter_row_is_locked_while_the_number_is_taken(): void
    {
        DB::enableQueryLog();

        app(ReceiptNumbers::class)->next(BookCode::Club);

        $locked = collect(DB::getQueryLog())->contains(fn (array $q): bool => str_contains(strtolower($q['query']), 'for update'));

        $this->assertTrue($locked, 'The receipt counter must be read with SELECT ... FOR UPDATE.');
    }

    public function test_a_number_is_not_used_up_when_the_payment_fails(): void
    {
        $numbers = app(ReceiptNumbers::class);

        try {
            DB::transaction(function () use ($numbers): void {
                $numbers->next(BookCode::Club);
                throw new \RuntimeException('payment failed');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertStringEndsWith('-0001', $numbers->next(BookCode::Club));
    }

    public function test_format(): void
    {
        $this->assertSame('NS-25-0311', ReceiptNumbers::format(BookCode::Nursery, 2025, 311));
    }
}
