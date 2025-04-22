<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequestDetail extends Model
{
    use HasFactory , HasUuids;

    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    public function detailProduct(): BelongsTo
    {
        return $this->belongsTo(ProductDetail::class, 'product_detail_id');
    }
}
