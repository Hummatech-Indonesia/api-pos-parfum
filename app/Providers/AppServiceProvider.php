<?php

namespace App\Providers;

use App\Contracts\Interfaces\ArticleInterface;
use App\Contracts\Interfaces\Auth\StoreInterface;
use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Repositories\ArticleRepository;
use App\Contracts\Repositories\Auth\StoreRepository;
use App\Contracts\Repositories\Auth\UserRepository;
use App\Contracts\Repositories\CategoryRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    private array $register = [
        CategoryInterface::class => CategoryRepository::class,
        ArticleInterface::class => ArticleRepository::class,
        UserInterface::class => UserRepository::class,
        StoreInterface::class => StoreRepository::class 
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
