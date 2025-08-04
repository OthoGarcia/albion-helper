<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class City extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'cities';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['name', 'type', 'region', 'notes', 'refine_type', 'refine_bonus_percentage'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'refine_bonus_percentage' => 'decimal:2',
    ];

    public function itemPrices()
    {
        return $this->hasMany(CityItemPrice::class, 'city_id', 'id');
    }
}
