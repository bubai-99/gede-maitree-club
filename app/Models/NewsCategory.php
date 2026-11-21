<?php

namespace App\Models;

use App\Models\Concerns\Bilingual;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A category of news posts (Pujas, Sports, Service, Club life...).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property ?string $name_bn
 * @property int $sort
 */
#[Fillable(['slug', 'name', 'name_bn', 'sort'])]
class NewsCategory extends Model
{
    use Bilingual;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<NewsPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'category_id');
    }
}
