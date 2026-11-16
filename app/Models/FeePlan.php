<?php

namespace App\Models;

use App\Enums\FeePeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'name_bn', 'amount', 'period', 'admission_fee', 'active'])]
class FeePlan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'admission_fee' => 'integer',
            'period' => FeePeriod::class,
            'active' => 'boolean',
        ];
    }
}
