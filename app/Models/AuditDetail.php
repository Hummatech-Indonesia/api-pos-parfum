<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id'];
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'audit_details';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
    public function productDetail()
    {
        return $this->belongsTo(ProductDetail::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function details()
    {
        return $this->belongsTo(ProductDetail::class , 'product_detail_id');
    }
}
