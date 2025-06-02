<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(Unit::class);
    }
    
    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }
}
