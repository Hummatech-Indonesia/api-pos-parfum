<?php

namespace App\Models;

use App\Models\Outlet;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\KategoriPengeluaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pengeluaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'pengeluaran';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";
    protected $guarded = [];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id', 'id');
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    public function kategori_pengeluaran(): BelongsTo
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id', 'id');
    }
}
