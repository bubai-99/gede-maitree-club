<?php

namespace App\Models;

use App\Enums\PlantCategory;
use App\Enums\PlantLight;
use App\Enums\PlantWater;
use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use Database\Factories\PlantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * A plant in the Maitree Nursery catalogue.
 */
#[Fillable([
    'slug', 'name', 'name_bn', 'category', 'pot', 'pot_bn', 'price', 'member_price', 'stock', 'is_new',
    'light', 'water', 'about', 'about_bn', 'tip', 'tip_bn', 'photo', 'active', 'sort', 'status', 'published_at',
])]
class Plant extends Model implements HasMedia
{
    /** @use HasFactory<PlantFactory> */
    use Bilingual, HasFactory, HasPhoto, LogsChanges, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PlantCategory::class,
            'light' => PlantLight::class,
            'water' => PlantWater::class,
            'price' => 'integer',
            'member_price' => 'integer',
            'stock' => 'integer',
            'is_new' => 'boolean',
            'active' => 'boolean',
            'sort' => 'integer',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function inStock(): bool
    {
        return $this->active && $this->stock > 0;
    }

    /**
     * @return HasMany<StockMove, $this>
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class);
    }
}
