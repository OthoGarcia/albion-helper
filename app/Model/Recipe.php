<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class Recipe extends Model
{
    protected array $fillable = ['item_unique_name','output_quantity','crafting_time','recipe_type','conditions', 'crafting_focus'];
    protected array $casts = ['conditions'=>'array'];
    public function item() { return $this->belongsTo(Item::class, 'item_unique_name'); }
    public function ingredients() { return $this->hasMany(RecipeIngredient::class, 'recipe_id'); }
}
