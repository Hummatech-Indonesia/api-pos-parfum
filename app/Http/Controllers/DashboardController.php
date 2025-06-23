<?php

namespace App\Http\Controllers;

use App\Helpers\BaseResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;


class DashboardController extends Controller
{
    private $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function index(): JsonResponse
    {
        try {
            $dashboard = $this->dashboardService->getDashboardByRole(auth()->user());
            return BaseResponse::OK('Dashboard berhasil dimuat', $dashboard);
        } catch (\Throwable $e) {
            return BaseResponse::Error('Terjadi kesalahan.', [
                'message' => $e->getMessage()
            ]);
        }
    }
}
