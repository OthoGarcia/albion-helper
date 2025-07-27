<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;
use App\Model\Recipe;

/**
 */
class Item extends Model
{
    
    protected string $primaryKey = "item_unique_name";
    protected string $keyType = 'string';
    public bool $incrementing = false;
    protected array $fillable = [
        'item_unique_name',
        'tier',
        'weight',
        'max_stack_size',
        'ui_sprite',
        'shop_category',
        'shop_subcategory1',
        'unlocked_to_craft',
        'shop_subcategory2',
        'item_value'
    ];
    protected array $casts = ['unlocked_to_craft'=>'boolean', 'item_unique_name'=>'string'];

    public function stats() { return $this->hasMany(ItemStat::class, 'item_unique_name'); }
    public function recipes() { return $this->hasMany(Recipe::class, 'item_unique_name'); }
    public function itemPrices()
    {
        return $this->hasMany(CityItemPrice::class, 'item_unique_name');
    }

    public function getItemUniqueName(): string
    {
        return $this->item_unique_name;
    }
}
