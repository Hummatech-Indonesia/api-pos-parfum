<?php

namespace App\Services;

use App\Contracts\Repositories\Auth\UserRepository;
use App\Contracts\Repositories\Master\ProductRepository;
use App\Contracts\Repositories\Master\ProductStockRepository;
use App\Contracts\Repositories\Transaction\TransactionRepository;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Outlet;
use App\Models\ProductStock;
use App\Models\User;


class DashboardService
{
    public function __construct(
        protected ProductRepository $productRepo,
        protected TransactionRepository $transactionRepo,
        protected ProductStockRepository $stockRepo,
        protected UserRepository $userRepo,
    ) {}

    public function getDashboardByRole(User $user, int $year)
    {
        $roles = $user->roles->pluck('name')->map(fn($r) => strtolower($r))->toArray();

        if (array_intersect($roles, ['warehouse', 'owner'])) {
            return $this->getWarehouseDashboard($user, $year);
        }

        if (in_array('outlet', $roles)) {
            return $this->getOutletDashboard($user, $year);
        }

        return ['error' => 'Role tidak dikenali'];
    }

    protected function getWarehouseDashboard(User $user, $year)
    {
        $storeId = $user->store_id;

        return [
            'role' => 'warehouse',
            'total_products' => $this->productRepo->countByStore($storeId),
            'total_orders' => $this->transactionRepo->countByStore($storeId),
            'total_retail' => $this->userRepo->countRetailByStore($storeId),
            'income_this_month' => $this->transactionRepo->sumThisMonth($storeId),
            'chart' => [
                'year' => $year,
                'data' => $this->transactionRepo->monthlyIncome($year, $storeId)
            ],
            'recent_orders' => $this->transactionRepo->recentOrdersByStore($storeId),
        ];
    }

    protected function getOutletDashboard(User $user, $year)
    {
        $storeId = $user->store_id;
        $userId = $user->id;
        $outletId = $user->outlet_id;

        return [
            'role' => 'outlet',
            'total_products' => $this->productRepo->countByStore($storeId),
            'total_orders' => $this->transactionRepo->countByUser($storeId, $userId),
            'total_users' => $this->userRepo->countOutletUsers($storeId, $outletId),
            'income_this_month' => $this->transactionRepo->sumThisMonth($storeId, $userId),
            'chart' => [
                'year' => $year,
                'data' => $this->transactionRepo->monthlyIncome($year, $storeId, $userId)
            ],
            'recent_orders' => $this->transactionRepo->recentOrdersByUser($storeId, $userId),
            'low_stock_products' => $this->stockRepo->lowStockByOutlet($outletId),
        ];
    }
}
