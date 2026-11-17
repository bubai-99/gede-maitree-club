<?php

namespace App\Http\Requests\Admin;

use App\Enums\ScanResult;
use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Changes an offline phone queued: scans (with what the phone decided), head counts, let back in
 * and undo, in the order they happened. At most 500 at a time.
 */
class ScanSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('scan', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ops' => ['required', 'array', 'min:1', 'max:500'],
            'ops.*.op' => ['required', Rule::in(['scan', 'people', 'let_in', 'undo'])],
            'ops.*.uuid' => ['required', 'uuid'],
            'ops.*.code' => ['nullable', 'string', 'max:200'],
            'ops.*.family_no' => ['nullable', 'string', 'max:20'],
            'ops.*.gate' => ['nullable', 'string', 'max:60'],
            'ops.*.scanned_at' => ['nullable', 'date'],
            'ops.*.at' => ['nullable', 'date'],
            'ops.*.local_result' => ['nullable', Rule::enum(ScanResult::class)],
            'ops.*.people' => ['nullable', 'integer', 'min:1', 'max:99'],
            'ops.*.let_in' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<array{op: string, uuid: string, code: ?string, family_no: ?string, gate: ?string, scanned_at: ?CarbonInterface, at: ?CarbonInterface, local_result: ?ScanResult, people: ?int, let_in: bool}>
     */
    public function operations(): array
    {
        /** @var array<int|string, array<string, mixed>> $ops */
        $ops = $this->validated('ops');

        return array_map(fn (array $op): array => [
            'op' => (string) $op['op'],
            'uuid' => strtolower((string) $op['uuid']),
            'code' => isset($op['code']) && $op['code'] !== '' ? (string) $op['code'] : null,
            'family_no' => isset($op['family_no']) && $op['family_no'] !== '' ? (string) $op['family_no'] : null,
            'gate' => isset($op['gate']) && $op['gate'] !== '' ? trim((string) $op['gate']) : null,
            'scanned_at' => isset($op['scanned_at']) ? Carbon::parse((string) $op['scanned_at']) : null,
            'at' => isset($op['at']) ? Carbon::parse((string) $op['at']) : null,
            'local_result' => isset($op['local_result']) ? ScanResult::from((string) $op['local_result']) : null,
            'people' => isset($op['people']) ? (int) $op['people'] : null,
            'let_in' => (bool) ($op['let_in'] ?? false),
        ], array_values($ops));
    }
}
