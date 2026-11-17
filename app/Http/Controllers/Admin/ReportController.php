<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\LedgerEntry;
use App\Payments\Reports;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Console → Reports: monthly collection summary and income and expenditure by head (the AGM
 * statement), for a financial year, with CSV downloads.
 */
class ReportController extends Controller
{
    public function index(Request $request, Reports $reports): View
    {
        Gate::authorize('viewReports', LedgerEntry::class);
        $year = $this->year($request);

        return view('admin.reports.index', [
            'year' => $year,
            'years' => range(Reports::currentYear(), 2014),
            'collections' => $reports->collections($year),
            'books' => $reports->incomeAndExpenditure($year, $this->bookIds()),
        ]);
    }

    public function collections(Request $request, Reports $reports): StreamedResponse
    {
        Gate::authorize('viewReports', LedgerEntry::class);
        $year = $this->year($request);

        $rows = $reports->collections($year)->map(fn (array $row): array => [
            $row['month']->format('Y-m'), $row['receipts'], $row['cash'], $row['upi'], $row['online'], $row['total'],
        ]);

        return Csv::download("collections-{$year}-".($year + 1).'.csv', ['Month', 'Receipts', 'Cash (₹)', 'UPI (₹)', 'Online (₹)', 'Total (₹)'], $rows);
    }

    public function incomeExpenditure(Request $request, Reports $reports): StreamedResponse
    {
        Gate::authorize('viewReports', LedgerEntry::class);
        $year = $this->year($request);

        $rows = [];

        foreach ($reports->incomeAndExpenditure($year, $this->bookIds()) as $book) {
            foreach (['income' => 'Income', 'expense' => 'Expense'] as $key => $label) {
                foreach ($book[$key] as $line) {
                    $rows[] = [$book['book']->code->englishLabel(), $label, $line['head']->englishLabel(), $line['amount']];
                }
            }
            $rows[] = [$book['book']->code->englishLabel(), 'Surplus', '', $book['total_income'] - $book['total_expense']];
        }

        return Csv::download("income-expenditure-{$year}-".($year + 1).'.csv', ['Book', 'Type', 'Head', 'Amount (₹)'], $rows);
    }

    private function year(Request $request): int
    {
        $request->validate(['year' => ['nullable', 'integer', 'min:2014', 'max:'.Reports::currentYear()]]);

        return $request->integer('year', Reports::currentYear());
    }

    /**
     * @return list<int>
     */
    private function bookIds(): array
    {
        return Book::query()->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
