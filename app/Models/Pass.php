<?php

namespace App\Models;

use App\Enums\PassStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A family's gate pass for one event (one per registration).
 *
 * The QR holds only "GMP1." and a random token: no family data can be read from it. The
 * database finds a pass by the SHA-256 hash of the token (token_hash); the token itself is
 * kept encrypted with the app key so the member app can draw the QR again. The gate
 * scanner's offline list carries hashes only.
 *
 * @property PassStatus $status
 * @property Carbon|null $used_at
 */
#[Fillable(['event_id', 'registration_id', 'family_id', 'token_hash', 'token', 'people', 'status', 'used_at', 'scanned_by', 'gate', 'entered_people'])]
#[Hidden(['token'])]
class Pass extends Model
{
    /** What every pass QR starts with. */
    public const PREFIX = 'GMP1.';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'people' => 'integer',
            'entered_people' => 'integer',
            'status' => PassStatus::class,
            'used_at' => 'datetime',
        ];
    }

    /**
     * A new random token (32 URL-safe characters, 192 bits).
     */
    public static function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * The token in a scanned QR, or null when the text is not a club pass.
     */
    public static function tokenFromCode(string $code): ?string
    {
        $code = trim($code);

        if (! Str::startsWith($code, self::PREFIX)) {
            return null;
        }

        $token = substr($code, strlen(self::PREFIX));

        return preg_match('/^[A-Za-z0-9_-]{32}$/', $token) === 1 ? $token : null;
    }

    /**
     * What the QR on the pass holds.
     */
    public function qrText(): string
    {
        return self::PREFIX.$this->token;
    }

    public function isActive(): bool
    {
        return $this->status === PassStatus::Active;
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
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

    /**
     * @return HasMany<PassScan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(PassScan::class);
    }
}
