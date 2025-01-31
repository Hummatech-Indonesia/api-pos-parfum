<?php

namespace App\Contracts\Interfaces\Auth;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface UserInterface extends GetInterface, StoreInterface, CustomQueryInterface, CustomPaginateInterface, ShowInterface, UpdateInterface, DeleteInterface
{
    public function checkUserActive(mixed $id): mixed;

    public function customQueryV2(array $data): mixed;
}