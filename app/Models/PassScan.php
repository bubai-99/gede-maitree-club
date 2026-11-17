<?php

namespace App\Models;

use App\Enums\ScanMethod;
use App\Enums\ScanResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One check at the gate. uuid is made by the scanner phone, so an offline scan uploaded twice
 * is stored once. conflict notes an offline scan that the server saw differently: "duplicate"
 * when another scan had let the same pass in earlier (first scan wins), "cancelled" when the
 * pass was cancelled after the phone downloaded the list.
 *
 * @property ScanResult $result
 * @property ScanMethod $method
 * @property Carbon $scanned_at
 * @property Carbon|null $undone_at
 */
#[Fillable(['uuid', 'event_id', 'pass_id', 'family_id', 'scanned_by', 'gate', 'method', 'result', 'people', 'let_in', 'offline', 'conflict', 'scanned_at', 'undone_at'])]
class PassScan extends Model
{
    /** A scan can be undone (or its head count changed) for this many seconds. */
    public const UNDO_SECONDS = 120;

    public const CONFLICT_DUPLICATE = 'duplicate';

    public const CONFLICT_CANCELLED = 'cancelled';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => ScanMethod::class,
            'result' => ScanResult::class,
            'people' => 'integer',
            'let_in' => 'boolean',
            'offline' => 'boolean',
            'scanned_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Pass, $this>
     */
    public function pass(): BelongsTo
    {
        return $this->belongsTo(Pass::class);
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function isUndone(): bool
    {
        return $this->undone_at !== null;
    }
}
