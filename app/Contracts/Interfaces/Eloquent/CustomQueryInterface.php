<?php

namespace App\Contracts\Interfaces\Eloquent;

interface CustomQueryInterface
{
    /**
     * Handle the Get all data event from models.
     *
     * @return mixed
     */

    public function customQuery(array $data): mixed;
}
