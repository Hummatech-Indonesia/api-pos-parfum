<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function detailProduct(){
        return $this->belongsTo(ProductDetail::class, 'product_detail_id');
    }

    public function store(){
        return $this->belongsTo(Store::class);
    }

    public function outlet(){
        return $this->belongsTo(Outlet::class);
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }

    public function detailRequestStock(): HasMany
    {
        return $this->hasMany(StockRequestDetail::class);
    }
}
