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

    public function users()
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles');
    }
}
