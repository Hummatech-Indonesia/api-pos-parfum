<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\ProfitLossRepository;
use App\Helpers\BaseResponse;
use Illuminate\Http\Request;

class ProfitLossController extends Controller
{
    private $profitLossRepository;
    public function __construct(ProfitLossRepository $profitLossRepository)
    {
        $this->profitLossRepository = $profitLossRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $outletId = auth()->user()->outlet_id;

        $data = $this->profitLossRepository->getOutletProfitLoss($outletId, $month, $year);

        return BaseResponse::Ok("Berhasil mengambil laporan", $data);
    }

    public function profitLossOutlet()
    {
        $user = auth()->user();

        if (!$user->hasRole('outlet')) {
            return BaseResponse::Custom(false, 'Role tidak sesuai. Hanya user dengan role outlet yang diizinkan', null, 403);
        }
        try {
            $outlet_id = auth()->user()->outlet_id ?? null;

            if (!$outlet_id) {
                return BaseResponse::Error('User tidak memiliki outlet', null, 400);
            }

            $data = $this->profitLossRepository->getOutletProfitLoss($outlet_id);

            return BaseResponse::Ok('Berhasil mengambil data laba rugi', $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal mengambil data laba rugi', $th->getMessage());
        }
    }

    public function profitLossWarehouse(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasRole('warehouse')) {
            return BaseResponse::Custom(false, 'Role tidak sesuai. Hanya pengguna dengan role warehouse yang diizinkan.', null, 403);
        }
        try {
            $warehouseId = auth()->user()->warehouse_id;

            if (!$warehouseId) {
                return BaseResponse::Error('User tidak terkait dengan warehouse', 400);
            }

            $data = $this->profitLossRepository->getWarehouseProfitLoss($warehouseId);

            return BaseResponse::Ok('Berhasil mengambil laporan warehouse', $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error('Gagal mengambil laporan warehouse', $th->getMessage());
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
