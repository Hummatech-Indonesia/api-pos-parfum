<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductBlendDetail extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    protected $casts = [
        'used_stock' => 'double',
    ];

    public function productBlend(): BelongsTo
    {
        return $this->belongsTo(ProductBlend::class);
    }

    public function productDetail(): BelongsTo
    {
        return $this->belongsTo(ProductDetail::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }
    
    public function productStock(): HasOne
    {
        return $this->hasOne(ProductStock::class, 'product_detail_id', 'product_detail_id')
        ->where('warehouse_id', auth()->user()->warehouse_id);
    }
}
