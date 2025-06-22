<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $casts = [
        'composition' => 'array',
    ];


    protected $guarded = [];

    /**
     * Get data relation belongs to with store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 
     */
    public function details(): HasMany
    {
        return $this->hasMany(ProductDetail::class)->where('is_delete',0);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->where('is_delete',0);
    }

    public function active_details(): HasMany
    {
        return $this->hasMany(ProductDetail::class)->where('is_delete',0);
    }

    public function discountVoucher(): HasMany
    {
        return $this->hasMany(DiscountVoucher::class)->where('is_delete',0);
    }

    public function discountVoucherActive(): HasMany
    {
        return $this->where('is_delete',0)->where('is_active',1)->hasMany(DiscountVoucher::class);
    }

    /**
     * Relation table product with product bundling
     */
    public function productBundling(): HasOne
    {
        return $this->hasOne(ProductBundling::class);
    }
}
