<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundlingDetail extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'product_bundling_id', 'product_detail_id', 'unit', 'unit_id', 'quantity',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function ProductDetail()
    {
        return $this->belongsTo(ProductDetail::class);
    }

    public function bundling()
    {
        return $this->belongsTo(ProductBundling::class, 'product_bundling_id');
    }

    public function unitRelation()
    {
        return $this->belongsTo(Unit::class, 'unit_id')->withTrashed();
    }

}