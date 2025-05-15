<?php 

namespace App\Contracts\Interfaces\Master;

use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\GetInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;
use App\Contracts\Interfaces\Eloquent\DeleteInterface;
use App\Contracts\Interfaces\Eloquent\ShowInterface;

interface RoleInterface extends StoreInterface, GetInterface, UpdateInterface, DeleteInterface, ShowInterface
{
    public function get(?int $perPage = null): mixed;

    public function restore(mixed $id): mixed;
}

