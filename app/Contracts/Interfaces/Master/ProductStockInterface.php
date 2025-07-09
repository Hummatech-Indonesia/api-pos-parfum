<?php

namespace App\Contracts\Interfaces\Master;

use App\Contracts\Interfaces\Eloquent\CustomPaginateInterface;
use App\Contracts\Interfaces\Eloquent\CustomQueryInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;
use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;
use App\Models\ProductStock;

interface ProductStockInterface extends GetInterface, StoreInterface, CustomQueryInterface, CustomPaginateInterface, ShowInterface, UpdateInterface, DeleteInterface
{
    public function checkActive(mixed $id): mixed;
    public function getFromProductDetail(mixed $product_detail_id);
    public function checkStock(mixed $product_detail_id);
    public function checkNewStock(mixed $product_detail_id, mixed $product_id);
    public function findByOutletAndProductDetail($outletId, $productDetailId);
    public function increaseStock($outletId, $productDetailId, $amount);
    public function updateOrCreateStock($outletId, $productDetailId, $stock): ProductStock;

}
