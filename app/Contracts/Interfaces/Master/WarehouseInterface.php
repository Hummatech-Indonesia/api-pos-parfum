<?php

namespace App\Contracts\Interfaces\Master;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;

interface WarehouseInterface extends GetInterface, StoreInterface, CustomQueryInterface, CustomPaginateInterface, ShowInterface, UpdateInterface, DeleteInterface
{
    public function checkActive(mixed $id): mixed;

    public function withProductStocks($warehouseId): mixed;

    public function getProductStocksPaginated($warehouseId, $perPage, $page): mixed;

}