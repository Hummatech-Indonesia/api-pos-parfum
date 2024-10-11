<?php

namespace App\Contracts\Interfaces\Eloquent;

use Illuminate\Pagination\LengthAwarePaginator;

interface CustomPaginateInterface
{
    /**
     * Handle paginate data event from models.
     *
     * @param int $pagination
     *
     * @return LengthAwarePaginator
     */

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed;
}
