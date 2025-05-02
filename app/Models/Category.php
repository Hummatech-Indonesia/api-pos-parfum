<?php

namespace App\Models;

use App\Base\Interfaces\HasArticles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model implements HasArticles
{
    use HasFactory;

    public $incrementing = false;

    public $keyType = 'string';
    protected $table = 'categories';
    protected $primaryKey = 'id';

    protected $guarded = [];

    /**
     * One-to-Many relationship with Article Model
     *
     * @return HasMany
     */

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

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
        return $this->hasMany(Product::class)->where('is_delete',0);
    }
}
