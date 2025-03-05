<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDetail extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    /**
     * Get data relation belongs to with product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->where('is_delete',0);
    }

    /**
     * Get data relation belongs to with category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->where('is_delete',0);
    }

    /**
     * Get data relation belongs to with varian
     */
    public function varian(): BelongsTo
    {
        return $this->belongsTo(ProductVarian::class, 'product_varian_id')->where('is_delete',0);
    }
}
