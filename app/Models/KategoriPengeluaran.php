<?php

namespace App\Models;

use App\Models\Outlet;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPengeluaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'kategori_pengeluaran';
    public $timestamps = true;
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

    /**
     * Get all of the comments for the KategoriPengeluaran
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expenditures(): HasMany
    {
        return $this->hasMany(Pengeluaran::class);
    }
}
