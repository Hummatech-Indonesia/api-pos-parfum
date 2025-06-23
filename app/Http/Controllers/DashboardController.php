<?php

namespace App\Http\Controllers;

use App\Helpers\BaseResponse;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class DashboardController extends Controller
{
    private $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function index(Request $request): JsonResponse
    {
        try {
            $year = (int) $request->query('year', now()->year);

            $dashboard = $this->dashboardService->getDashboardByRole(auth()->user(), $year);
            return BaseResponse::OK('Dashboard berhasil dimuat', $dashboard);
        } catch (\Throwable $e) {
            return BaseResponse::Error('Terjadi kesalahan.', [
                'message' => $e->getMessage()
            ]);
        }
    }
}
