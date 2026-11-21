<?php

namespace App\Models;

use App\Contracts\PublishesContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use App\Support\SiteSettings;
use Database\Factories\CateringPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Maitree Serving package. The club serves; it does not cook. The rate is per guest.
 */
#[Fillable(['slug', 'name', 'name_bn', 'for', 'for_bn', 'rate', 'min_guests', 'popular', 'menu', 'menu_bn', 'sort', 'status', 'published_at'])]
class CateringPackage extends Model implements PublishesContent
{
    /** @use HasFactory<CateringPackageFactory> */
    use Bilingual, HasFactory, LogsChanges, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'integer',
            'min_guests' => 'integer',
            'popular' => 'boolean',
            'menu' => 'array',
            'menu_bn' => 'array',
            'sort' => 'integer',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Rate for members, after the member discount.
     */
    public function memberRate(): int
    {
        $discount = app(SiteSettings::class)->memberDiscount();

        return (int) round($this->rate * (100 - $discount) / 100);
    }

    /**
     * Servers needed: one for every 15 guests, at least two.
     */
    public static function serversFor(int $guests): int
    {
        return max(2, (int) ceil($guests / 15));
    }

    /**
     * @return HasMany<CateringOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(CateringOrder::class, 'package_id');
    }
}
