<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountVoucher extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    /**
     * Get data relation belongs to with store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get data relation belongs to with outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->where('is_delete', 0);
    }

    /**
     * Get data relation belongs to with product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->where('is_delete', 0);
    }

    public function details()
    {
        return $this->belongsTo(ProductDetail::class,'product_detail_id')->where('is_delete', 0);
    }
}
