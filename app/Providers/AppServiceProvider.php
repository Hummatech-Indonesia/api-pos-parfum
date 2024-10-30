<?php

namespace App\Providers;

use App\Contracts\Interfaces\ArticleInterface;
use App\Contracts\Interfaces\Auth\StoreInterface;
use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Interfaces\Master\OutletInterface;
use App\Contracts\Interfaces\Master\ProductVarianInterface;
use App\Contracts\Interfaces\Master\WarehouseInterface;
use App\Contracts\Repositories\ArticleRepository;
use App\Contracts\Repositories\Auth\StoreRepository;
use App\Contracts\Repositories\Auth\UserRepository;
use App\Contracts\Repositories\CategoryRepository;
use App\Contracts\Repositories\Master\OutletRepository;
use App\Contracts\Repositories\Master\ProductVarianRepository;
use App\Contracts\Repositories\Master\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    private array $register = [
        CategoryInterface::class => CategoryRepository::class,
        ArticleInterface::class => ArticleRepository::class,
        UserInterface::class => UserRepository::class,
        StoreInterface::class => StoreRepository::class,
        WarehouseInterface::class => WarehouseRepository::class,
        OutletInterface::class => OutletRepository::class,
        ProductVarianInterface::class => ProductVarianRepository::class 
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach ($this->register as $index => $value) $this->app->bind($index, $value);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
