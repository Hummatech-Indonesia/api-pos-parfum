<?php
        
namespace App\Contracts\Interfaces;
        
use App\Contracts\Interfaces\Eloquent\DeleteInterface; 
use App\Contracts\Interfaces\Eloquent\GetInterface; 
use App\Contracts\Interfaces\Eloquent\ShowInterface; 
use App\Contracts\Interfaces\Eloquent\StoreInterface; 
use App\Contracts\Interfaces\Eloquent\UpdateInterface;
        
interface ProductBlendInterface extends GetInterface, StoreInterface, UpdateInterface, ShowInterface, DeleteInterface
{
    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed;
    public function show(mixed $id): mixed;
    public function getDetailWithPagination(string $id, int $page = 1, int $perPage = 5);
}