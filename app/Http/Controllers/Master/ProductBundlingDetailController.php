<?php

namespace App\Http\Controllers\Master;

use App\Contracts\Interfaces\Master\ProductBundlingDetailInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductBundlingDetailRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBundlingDetailController extends Controller
{
    private ProductBundlingDetailInterface $productBundlingDetail;

    public function __construct(ProductBundlingDetailInterface $productBundlingDetail)
    {
        $this->productBundlingDetail = $productBundlingDetail;
    }

    public function index(Request $request)
    {
        try {
            if ($request->has('paginate') && $request->paginate == 1) {
                $perPage = $request->input('per_page', 10);
                $page = $request->input('page', 1);

                $data = $this->productBundlingDetail->paginate($perPage)->toArray();

                $result = $data['data'];
                unset($data['data']);

                return BaseResponse::Paginate("Berhasil mengambil data product bundling detail", $result, $data);
            } else {
                $result = $this->productBundlingDetail->get();
                return BaseResponse::Ok("Berhasil mengambil data product bundling detail", $result);
            }
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }


    public function store(ProductBundlingDetailRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $result = $this->productBundlingDetail->store($data);
            DB::commit();
            return BaseResponse::Ok("Berhasil menyimpan data product bundling detail", $result);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function update(ProductBundlingDetailRequest $request, string $id)
    {
        // dd($request->all());
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $result = $this->productBundlingDetail->update($id, $data);
            DB::commit();
            return BaseResponse::Ok("Berhasil memperbarui data product bundling detail", $result);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $this->productBundlingDetail->delete($id);
            DB::commit();
            return BaseResponse::Ok("Berhasil menghapus data ", null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function restore(string $id)
    {
        DB::beginTransaction();
        try {
            $model = $this->productBundlingDetail->show($id);
            $model->restore();
            DB::commit();
            return BaseResponse::Ok("Berhasil me-restore data", $model);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
