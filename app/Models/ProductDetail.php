<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Get data stock in outlet
     */
    public function productStockOutlet(): HasOne
    {
        return $this->hasOne(ProductStock::class)->where('outlet_id',auth()->user()->outlet_id);
    }

    /**
     * Get data stock in warehouse
     */
    public function productStockWarehouse(): HasOne
    {
        return $this->hasOne(ProductStock::class)->where('warehouse_id',auth()->user()->warehouse_id);
    }
}
