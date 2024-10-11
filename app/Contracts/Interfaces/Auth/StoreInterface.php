<?php

namespace App\Contracts\Interfaces\Auth;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface as EloquentStoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface StoreInterface extends GetInterface, EloquentStoreInterface, CustomQueryInterface, CustomPaginateInterface, ShowInterface, UpdateInterface
{

}