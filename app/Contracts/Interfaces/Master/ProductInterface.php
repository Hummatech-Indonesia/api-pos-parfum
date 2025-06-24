<?php

namespace App\Contracts\Interfaces\Master;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface ProductInterface extends GetInterface, StoreInterface, CustomQueryInterface, CustomPaginateInterface, ShowInterface, UpdateInterface, DeleteInterface
{
    public function checkActive(mixed $id): mixed;
    public function checkActiveWithDetail(mixed $id): mixed;
    public function checkActiveWithDetailV2(mixed $id): mixed;
    public function getListProduct(array $filters = []): mixed;
}