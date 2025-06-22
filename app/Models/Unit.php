<?php

namespace App\Models;

use App\Models\ProductBlend;
use App\Models\ProductBlendDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];

    public function productBlends()
    {
        return $this->hasMany(ProductBlend::class);
    }
    
    public function productBlendDetails()
    {
        return $this->hasMany(ProductBlendDetail::class);
    }
    
    public function audit(){
        return $this->hasMany(AuditDetail::class);
    }

    public function productBundlingDetail() {
        return $this->hasMany(ProductBundlingDetail::class);
    }
}
