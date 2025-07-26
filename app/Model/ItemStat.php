<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 */
class ItemStat extends Model
{
    public bool $timestamps = false;
    protected string $primaryKey = 'item_id';
    protected array $fillable = ['item_id','stat_name','stat_value'];
    public function item() { return $this->belongsTo(Item::class, 'item_id'); }
}
