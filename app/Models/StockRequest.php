<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $guarded = [];
}
