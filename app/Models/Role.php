<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'roles';

    protected $primaryKey = 'id';

    protected $dates = ['deleted_at'];

    protected $appends = ['status'];


    public function users()
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles');
    }

    public function getStatusAttribute(): string
    {
        return is_null($this->deleted_at) ? 'Aktif' : 'Nonaktif';
    }
}
