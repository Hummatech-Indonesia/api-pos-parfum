<?php

namespace App\Contracts\Interfaces\Master;

use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface ProductBundlingInterface extends GetInterface, StoreInterface, ShowInterface, UpdateInterface, DeleteInterface, CustomQueryInterface, CustomPaginateInterface
{
    public function restore(mixed $id): mixed;

    public function paginate(int $perPage = 10): mixed;
}
