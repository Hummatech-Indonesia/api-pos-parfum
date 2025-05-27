<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundling extends Model
{
    use HasFactory,SoftDeletes, HasUuids;

    protected $fillable = [
        'id', 'product_id', 'name', 'description', 'category_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function details()
    {
        return $this->hasMany(ProductBundlingDetail::class, 'product_bundling_id');
    }


}
