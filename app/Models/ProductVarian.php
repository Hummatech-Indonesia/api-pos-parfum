<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVarian extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    /**
     * Get data relation one to many with product detail
     */
    public function productDetails(): HasMany
    {
        return $this->hasMany(ProductDetail::class)->where('is_delete',0);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);  
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProductDetail::class)->where('is_delete',0);
    }
}
