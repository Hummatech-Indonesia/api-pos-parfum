<?php

namespace App\Providers;

use App\Contracts\Interfaces\ArticleInterface;
use App\Contracts\Interfaces\Auth\StoreInterface;
use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\OutletInterface;
use App\Contracts\Interfaces\Master\ProductBundlingInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\ProductVarianInterface;
use App\Contracts\Interfaces\Master\RoleInterface;
use App\Contracts\Interfaces\Master\StockRequestInterface;
use App\Contracts\Interfaces\Master\StockRequestDetailInterface;
use App\Contracts\Interfaces\Master\WarehouseInterface;
use App\Contracts\Interfaces\Master\WarehouseStockInterface;
use App\Contracts\Interfaces\SettingInterface;
use App\Contracts\Interfaces\Transaction\ShiftUserInterface;
use App\Contracts\Interfaces\Transaction\TransactionDetailInterface;
use App\Contracts\Interfaces\Transaction\TransactionInterface;
use App\Contracts\Interfaces\Transaction\VoucherUsedInterface;
use App\Contracts\Interfaces\Master\ProductBundlingDetailInterface;
use App\Contracts\Repositories\ArticleRepository;
use App\Contracts\Repositories\Auth\StoreRepository;
use App\Contracts\Repositories\Auth\UserRepository;
use App\Contracts\Repositories\CategoryRepository;
use App\Contracts\Repositories\Master\DiscountVoucherRepository;
use App\Contracts\Repositories\Master\OutletRepository;
use App\Contracts\Repositories\Master\ProductBundlingDetailRepository;
use App\Contracts\Repositories\Master\ProductBundlingRepository;
use App\Contracts\Repositories\Master\ProductDetailRepository;
use App\Contracts\Repositories\Master\ProductRepository;
use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Contracts\Repositories\Master\ProductVarianRepository;
use App\Contracts\Repositories\Master\RoleRepository;
use App\Contracts\Repositories\Master\StockRequestRepository;
use App\Contracts\Repositories\Master\StockRequestDetailRepository;
use App\Contracts\Repositories\Master\WarehouseRepository;
use App\Contracts\Repositories\Master\WarehouseStockRepository;
use App\Contracts\Repositories\SettingRepository;
use App\Contracts\Repositories\Transaction\ShiftUserRepository;
use App\Contracts\Repositories\Transaction\TransactionDetailRepository;
use App\Contracts\Repositories\Transaction\TransactionRepository;
use App\Contracts\Repositories\Transaction\VoucherUsedRepository;
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
        ProductVarianInterface::class => ProductVarianRepository::class,
        ProductDetailInterface::class => ProductDetailRepository::class,
        ProductInterface::class => ProductRepository::class,
        DiscountVoucherInterface::class => DiscountVoucherRepository::class,
        StockRequestInterface::class => StockRequestRepository::class,
        StockRequestDetailInterface::class => StockRequestDetailRepository::class,
        WarehouseStockInterface::class => WarehouseStockRepository::class,
        ProductStockInterface::class => ProductStockRepository::class,
        TransactionInterface::class => TransactionRepository::class,
        TransactionDetailInterface::class => TransactionDetailRepository::class,
        VoucherUsedInterface::class => VoucherUsedRepository::class,
        ShiftUserInterface::class => ShiftUserRepository::class,
        SettingInterface::class => SettingRepository::class,
        RoleInterface::class => RoleRepository::class,
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
