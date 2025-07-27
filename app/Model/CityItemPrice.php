<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use App\Model\City;
use App\Model\Item;

/**
 */
class CityItemPrice extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'city_item_prices';

    protected array $fillable = [
        'city_id',
        'item_unique_name',
        'quality',
        'sell_price_min',
        'sell_price_min_date',
        'sell_price_max',
        'sell_price_max_date',
        'buy_price_min',
        'buy_price_min_date',
        'buy_price_max',
        'buy_price_max_date',
    ];

    protected array $casts = [
        'sell_price_min_date' => 'datetime',
        'sell_price_max_date' => 'datetime',
        'buy_price_min_date' => 'datetime',
        'buy_price_max_date' => 'datetime',
    ];

    public function city() { return $this->belongsTo(City::class); }
    public function item() { return $this->belongsTo(Item::class, 'item_unique_name', 'item_unique_name'); }
}
