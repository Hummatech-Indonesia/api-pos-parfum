<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->where('is_delete', 0);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function productDetail(): BelongsTo
    {
        return $this->belongsTo(ProductDetail::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
