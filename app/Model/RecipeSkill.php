<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class RecipeSkill extends Model
{
    public bool $timestamps = false;
    protected array $fillable = ['id', 'recipe_id','skill_name','skill_level','experience','boostable'];
    public function recipe() { return $this->belongsTo(Recipe::class); }
}
