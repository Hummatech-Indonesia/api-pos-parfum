<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Base\Interfaces\HasArticles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasArticles
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_id',
        'warehouse_id',
        'outlet_id',
        'is_delete',
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'is_delete'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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
     * One to one relationship with data store
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    /**
     * user in this store
     */
    public function related_store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id','id');
    }

    /**
     * user in this outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->where('is_delete',0);
    }

    /**
     * user in this outlet
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->where('is_delete',0);
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }
}
