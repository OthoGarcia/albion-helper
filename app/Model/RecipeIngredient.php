<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class RecipeIngredient extends Model
{
    public bool $timestamps = false;
    protected array $fillable = ['id', 'recipe_id','ingredient_item_unique_name','quantity', 'enchantment_level'];
    public function recipe() { return $this->belongsTo(Recipe::class); }
    public function item() { return $this->belongsTo(Item::class, 'ingredient_item_unique_name'); }
}
