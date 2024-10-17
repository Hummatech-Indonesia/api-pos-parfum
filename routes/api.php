<?php

use App\Helpers\BaseResponse;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Master\OutletController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\Uma\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::get('unauthorized', function (){
    return BaseResponse::Custom(false, 'Unauthorized', null, 401);
})->name('unauthorized');

// API AUTHENTIKASI
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

// API FOR AUTHENTIKASI
Route::middleware('auth:sanctum')->group(function() {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('me', [AuthController::class, 'getMe'])->name('get-me');

    // API FOR ROLE OWNER
    Route::middleware('role:owner')->group(function (){
        // API FOR DATA USER
        Route::resource("users", UserController::class)->only(['store','destroy','update']);
        // API FOR DATA OUTLET
        Route::resource("outlets", OutletController::class)->only(['store','destroy','update']);
        Route::resource("warehouses", WarehouseController::class)->only(['store','destroy','update']);
    });
    
    // API FOR DATA USER
    Route::resource("users", UserController::class)->except(['store','destroy','update']);
    // API FOR DATA OUTLET
    Route::resource("outlets", OutletController::class)->except(['store','destroy','update']);
    Route::resource("warehouses", WarehouseController::class)->except(['store','destroy','update']);
});