<?php

namespace App\Contracts\Interfaces\Master;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface UnitInterface extends GetInterface, StoreInterface, CustomPaginateInterface, ShowInterface, UpdateInterface, DeleteInterface
{
    public function allDataTrashed(): mixed;

    public function all(): mixed;

    public function cekUnit(mixed $name, mixed $code);
}