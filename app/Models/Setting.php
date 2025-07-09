<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $guarded = ['id'];
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'settings';
    protected $primaryKey = 'id';

    public function store()
{
    return $this->belongsTo(Store::class);
}

}
