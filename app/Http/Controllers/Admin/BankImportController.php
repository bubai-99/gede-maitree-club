<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankColumnsRequest;
use App\Http\Requests\Admin\BankStatementRequest;
use App\Models\Payment;
use App\Models\User;
use App\Payments\BankStatement;
use App\Payments\PaymentException;
use App\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Console → Online payments → Import bank statement: upload the CSV from the bank, say
 * which columns are which, check the matches, and confirm all matched UPI payments in one go.
 * The file is kept on the private disk only until it is confirmed.
 */
class BankImportController extends Controller
{
    private const DISK = 'local';

    public function create(): View
    {
        Gate::authorize('import', Payment::class);

        return view('admin.payments.import', [
            'waiting' => Payment::query()->where('status', PaymentStatus::AwaitingConfirmation->value)->count(),
        ]);
    }

    public function store(BankStatementRequest $request): RedirectResponse
    {
        $token = (string) Str::uuid();
        $file = $request->file('statement');
        abort_unless($file !== null && ! is_array($file), 422);

        $file->storeAs('imports', $token.'.csv', self::DISK);
        $request->session()->put('bank_imports.'.$token, $file->getClientOriginalName());

        return redirect()->route('admin.payments.import.show', $token);
    }

    public function show(Request $request, string $token): View
    {
        Gate::authorize('import', Payment::class);

        $statement = $this->statement($request, $token);
        $request->validate([
            'date' => ['nullable', 'integer', 'min:0', 'max:60'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:60'],
            'reference' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        $columns = $request->has('amount')
            ? ['date' => $request->filled('date') ? $request->integer('date') : null, 'amount' => $request->integer('amount'), 'reference' => $request->integer('reference')]
            : $statement->guessColumns();

        $result = $columns['amount'] !== null && $columns['reference'] !== null
            ? $statement->match($columns['amount'], $columns['reference'], $columns['date'])
            : null;

        return view('admin.payments.import-preview', [
            'token' => $token,
            'fileName' => (string) $request->session()->get('bank_imports.'.$token),
            'header' => $statement->header,
            'lineCount' => count($statement->rows),
            'columns' => $columns,
            'result' => $result,
        ]);
    }

    public function confirm(BankColumnsRequest $request, string $token, PaymentService $payments): RedirectResponse
    {
        $statement = $this->statement($request, $token);
        $user = $request->user();
        assert($user instanceof User);

        $result = $statement->match(
            $request->integer('amount'),
            $request->integer('reference'),
            $request->filled('date') ? $request->integer('date') : null,
        );

        $confirmed = 0;

        foreach ($result['matched'] as $row) {
            try {
                $payments->confirm($row['payment'], $user);
                $confirmed++;
            } catch (PaymentException) {
                // Someone else confirmed or rejected it meanwhile.
            }
        }

        Storage::disk(self::DISK)->delete('imports/'.$token.'.csv');
        $request->session()->forget('bank_imports.'.$token);

        activity('payments')->causedBy($user)
            ->withProperties(['confirmed' => $confirmed, 'unmatched' => count($result['unmatched'])])
            ->event('bank_import')
            ->log("Bank statement matched: {$confirmed} UPI payments confirmed");

        return redirect()->route('admin.payments.index')
            ->with('status', ':count UPI payments confirmed from the bank statement.')
            ->with('status_replace', ['count' => $confirmed]);
    }

    /**
     * The uploaded file, only for the person who uploaded it (kept in their session).
     */
    private function statement(Request $request, string $token): BankStatement
    {
        $path = 'imports/'.$token.'.csv';

        abort_unless($request->session()->has('bank_imports.'.$token) && Storage::disk(self::DISK)->exists($path), 404);

        return BankStatement::fromFile(Storage::disk(self::DISK)->path($path));
    }
}
