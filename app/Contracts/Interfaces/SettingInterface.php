<?php

namespace App\Contracts\Interfaces;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface SettingInterface extends GetInterface, StoreInterface, UpdateInterface, DeleteInterface, ShowInterface, CustomQueryInterface, CustomPaginateInterface
{
    public function allDataTrashed(array $payload = []): mixed;

    public function restore(string $id);
}
