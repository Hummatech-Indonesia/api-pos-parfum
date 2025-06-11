<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class)->where('is_delete',0);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->where('is_delete',0);
    }

    public function discountVouchers(): HasMany
    {
        return $this->hasMany(DiscountVoucher::class)->where('is_delete',0);
    }

    public function discountVoucherActive(): HasMany
    {
        return $this->where('is_delete',0)->where('is_active',1)->hasMany(ProductDetail::class);
    }

    public function transactions() : HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function outlets() : HasMany
    {
        return $this->hasMany(Outlet::class);
    }

}
