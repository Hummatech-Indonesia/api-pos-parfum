<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Master\UnitService;
use App\Http\Requests\Master\UnitRequest;
use App\Contracts\Repositories\Master\UnitRepository;

class UnitController extends Controller
{
    private UnitRepository $unit;
    private UnitService $unitService;

    public function __construct(UnitRepository $unit, UnitService $unitService)
    {
        $this->unit = $unit;
        $this->unitService = $unitService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = [];

        // check query filter
        if ($request->search) $payload["search"] = $request->search;

        try {
            $data = $this->unit->customPaginate($per_page, $page, $payload)->toArray();

            $result = $data["data"];
            unset($data["data"]);

            return BaseResponse::Paginate('Berhasil mengambil list data unit!', $result, $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $mapping = $this->unitService->dataUnit($data);
            $result_unit = $this->unit->store($mapping);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat unit', $result_unit);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $check_unit = $this->unit->show($id);
        if (!$check_unit) return BaseResponse::Notfound("Tidak dapat menemukan data unit!");

        return BaseResponse::Ok("Berhasil mengambil detail unit!", $check_unit);
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
    public function update(UnitRequest $request, string $id)
    {
        $data = $request->validated();

        // kenapa pakai show karena memakai fitur bawaan softdelete dari laravel jadi data yang sudah didelete tidak akan tampil do show function
        $check = $this->unit->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data unit!");

        DB::beginTransaction();
        try {
            $mapping = $this->unitService->dataUnit($data);
            $this->unit->update($id, $mapping);

            DB::commit();
            return BaseResponse::Ok('Berhasil update data unit', ["name" => $request->name, "code" => $request->code]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // kenapa pakai show karena memakai fitur bawaan softdelete dari laravel jadi data yang sudah didelete tidak akan tampil do show function
        $check = $this->unit->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data unit!");

        if (
            $check->productBlends()->exists() ||
            $check->productBlendDetails()->exists() ||
            $check->audit()->exists() ||
            $check->productBundlingDetail()->exists()
        ) {
            return BaseResponse::Error("Unit tidak dapat dihapus karena masih digunakan dalam relasi lain.", null);
        }

        DB::beginTransaction();
        try {
            $result_unit = $this->unit->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil delete data unit', $result_unit);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function list(Request $request)
    {
        try {
            $payload = [];
            if ($request->search) $payload['search'] = $request->search;
            $data = $this->unit->customQuery($payload)->get();


            return BaseResponse::Ok("Berhasil mengambil data unit", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function trashed(Request $request)
    {
        try {
            $data = $this->unit->allDataTrashed();

            return BaseResponse::Ok("Berhasil mengambil data unit", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
