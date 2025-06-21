<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class Audit extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $guarded = ['id'];
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'audits';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function productDetail()
    {
        $this->belongsTo(ProductDetail::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function auditDetails()
    {
        return $this->hasMany(AuditDetail::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function details()
    {
        return $this->hasMany(AuditDetail::class);
    }
}
