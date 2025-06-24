<?php

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BelajarController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Uma\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\StockRequestController;
use App\Http\Controllers\Master\ProductDetailController;
use App\Http\Controllers\Master\ProductVarianController;
use App\Http\Controllers\Transaction\ShiftUserController;
use App\Http\Controllers\Master\DiscountVoucherController;
use App\Http\Controllers\Master\ProductBundlingController;
use App\Http\Controllers\Master\ProductBundlingDetailController;
use App\Http\Controllers\ProductBlendController;
use App\Http\Controllers\Transaction\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('unauthorized', function () {
    return BaseResponse::Custom(false, 'Unauthorized', null, 401);
})->name('unauthorized');

// API AUTHENTIKASI
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

// API FOR AUTHENTIKASI
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('me', [AuthController::class, 'getMe'])->name('get-me');
    Route::get('roles', [UserController::class, 'listRole'])->name('get-roles');

    // API FOR ROLE OUTLET & OWNER & GUDANG
    Route::middleware('role:outlet|owner|warehouse')->group(function () {
        Route::get('stock-request/no-paginate', [StockRequestController::class, 'listStockRequest'])->name('list-stock-request-no-paginate');
        Route::resource("stock-request", StockRequestController::class)->only(['update']);
        Route::get('product-details/stock', [ProductDetailController::class, 'stockProduct']);
        Route::post('warehouses/add/stock', [WarehouseController::class, 'warehouseStock']);
        Route::get('warehouses/history/stock', [WarehouseController::class, 'listWarehouseStock']);
    });

        // API FOR ROLE OWNER & WAREHOUSE
    Route::middleware('role:owner|warehouse|outlet')->group(function () {
        Route::Resource('/product-bundling', ProductBundlingController::class);
        Route::post('/product-bundling/{id}/restore', [ProductBundlingController::class, 'restore']);
        Route::apiResource('/product-bundling-detail', ProductBundlingDetailController::class);
        Route::post('/product-bundling-detail/{id}/restore', [ProductBundlingDetailController::class, 'restore']);
    });

    Route::middleware('role:owner|warehouse')->group(function () {
        Route::apiResource("product-blend", ProductBlendController::class);
    });

    // API FOR ROLE OWNER
    Route::middleware('role:owner|warehouse|outlet')->group(function () {
        // API FOR DATA USER
        Route::get('users/no-paginate', [UserController::class, 'listUser'])->name('list-users-no-paginate');
        Route::get('users/v2/no-paginate', [UserController::class, 'listUserV2'])->name('list-users-no-paginate.v2');
        Route::resource("users", UserController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA OUTLET
        Route::get('outlets/no-paginate', [OutletController::class, 'listOutlet'])->name('list-outlets-no-paginate');
        Route::resource("outlets", OutletController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA WAREHOUSE
        Route::get('warehouses/no-paginate', [WarehouseController::class, 'listWarehouse'])->name('list-warehouses-no-paginate');
        Route::resource("warehouses", WarehouseController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA PRODUCT
        Route::get('products/no-paginate', [ProductController::class, 'listProduct'])->name('list-products-no-paginate');
        Route::resource("products", ProductController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA PRODUCT DETAIL
        Route::get('product-details/no-paginate', [ProductDetailController::class, 'listProduct'])->name('list-product-details-no-paginate');
        Route::resource("product-details", ProductDetailController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA CATEGORY
        Route::get('categories/no-paginate', [CategoryController::class, 'listCategory'])->name('list-categories-no-paginate');
        Route::resource("categories", CategoryController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA PRODUCT VARIAN
        Route::get('variants/no-paginate', [ProductVarianController::class, 'listProductVarian'])->name('list-variants-no-paginate');
        Route::resource("variants", ProductVarianController::class)->only(['store', 'destroy', 'update']);
        // API FOR DATA DISCOUNT VOUCHER
        Route::get('discount-vouchers/no-paginate', [DiscountVoucherController::class, 'listDiscountVoucher'])->name('list-discount-vouchers-no-paginate');
        Route::resource("discount-vouchers", DiscountVoucherController::class)->only(['store', 'destroy', 'update']);
    });

    Route::middleware(['auth:sanctum', 'role:admin|warehouse'])->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::post('roles/{id}/restore', [RoleController::class, 'restore']);
        Route::delete('roles/{role}/users/{user}', [RoleController::class, 'detachUser']);
        Route::resource("stock-request", StockRequestController::class)->only(['index', 'show']);
    });

    // API FOR ROLE OUTLET & OWNER
    Route::middleware('role:outlet|owner')->group(function () {
        Route::resource("stock-request", StockRequestController::class)->only(['store', 'destroy']);
    });

    Route::middleware('role:outlet|admin|warehouse')->group(function () {
        Route::post('audit/{id}/restore', [AuditController::class, 'restore']);
        Route::get("audit/no-paginate", [AuditController::class, 'list']);
        Route::get("audit/alltrashed", [AuditController::class, 'trashed']);
        Route::put('/audit/{id}/detail', [AuditController::class, 'updateStatusWithProducts']);
        Route::resource("audit", AuditController::class)->only(['destroy', 'index', 'show']);
    });

    Route::middleware('role:auditor|admin|owner')->group(function () {
        Route::post('audit/{id}/restore', [AuditController::class, 'restore']);
        Route::resource("audit", AuditController::class)->only(['store', 'destroy', 'update']);
    });

    // API FOR ROLE ADMIN, WAREHOUSE & OWNER
    Route::middleware('role:admin|owner|warehouse')->group(function () {
        Route::post('setting/{id}/restore', [SettingController::class, 'restore']);
        Route::get("setting/no-paginate", [SettingController::class, 'listWSetting']);
        Route::get("setting/alltrashed", [SettingController::class, 'trashed']);
        Route::get("audit/no-paginate", [AuditController::class, 'list']);
        Route::get("audit/alltrashed", [AuditController::class, 'trashed']);
        Route::resource("setting", SettingController::class);
        // Route::get('/dashboard', [DashbordController::class, 'index']);
    });

    // API FOR DATA USER
    Route::post("users/sync", [UserController::class, 'syncStoreData']);
    Route::resource("users", UserController::class)->except(['store','destroy','update']);
    // API FOR DATA OUTLET
    Route::resource("outlets", OutletController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA WAREHOUSE
    Route::resource("warehouses", WarehouseController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA PRODUCT
    Route::resource("products", ProductController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA PRODUCT DETAIL
    Route::resource("product-details", ProductDetailController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA CATEGORY
    Route::resource("categories", CategoryController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA PRODUCT VARIANTS
    Route::resource("variants", ProductVarianController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA DISCOUNT VOUCHER
    Route::resource("discount-vouchers", DiscountVoucherController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA STOCK REQUEST
    Route::resource("stock-request", StockRequestController::class)->except(['store', 'destroy', 'update']);
    // API FOR DATA SETTING
    Route::resource("setting", SettingController::class)->except(['store', 'destroy', 'update']);

    // API FOR DATA TRANSACTION
    Route::get("transactions/no-paginate", [TransactionController::class, 'getData']);
    Route::post("transactions/sync", [TransactionController::class, 'syncStoreData']);
    Route::resource("transactions", TransactionController::class)->except(['destroy']);
    // API FOR DATA SHIFT
    Route::post("shifts/sync", [ShiftUserController::class, 'syncStoreData']);
    Route::get("shifts/no-paginate", [ShiftUserController::class, 'getData']);
    Route::resource("shifts", ShiftUserController::class)->except(['destroy']);
    // API FOR UNIT
    Route::get("unit/no-paginate", [UnitController::class, 'list']);
    Route::get("unit/alltrashed", [UnitController::class, 'trashed']);
    Route::resource("unit", UnitController::class)->except(['create', 'edit']);

    Route::middleware(['role:warehouse|outlet|admin'])->get('/dashboard', [DashboardController::class, 'index']);
});
