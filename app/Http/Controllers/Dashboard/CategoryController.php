<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\Interfaces\CategoryInterface;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    private CategoryService $categoryService;
    private CategoryInterface $category;

    public function __construct(CategoryService $categoryService, CategoryInterface $category)
    {
        $this->categoryService = $categoryService;
        $this->category = $category;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $sort = $request->sort ?? 'created_at';
        $order = $request->order ?? 'ASC';
        $payload = [
            "is_delete" => 0,
        ];

        // check query filter
        if ($request->search) $payload["search"] = $request->search;
        if ($request->start_date) $payload["start_date"] = $request->start_date;
        if ($request->end_date) $payload["end_date"] = $request->end_date;
        if ($request->is_delete) $payload["is_delete"] = $request->is_delete;
        if (auth()->user()->hasRole('warehouse')) $payload["warehouse_id"] = auth()->user()->warehouse_id;
        if (auth()->user()->hasRole('outlet')) $payload["outlet_id"] = auth()->user()->outlet_id;

        // add sorting
        $sorting = $this->category->sorted($sort, $order);
        $payload['sorting'] = $sorting;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        $data = $this->category->customPaginate($per_page, $page, $payload);
        $meta = PaginationHelper::meta($data);

        $result = $data->items();

        return BaseResponse::Paginate('Berhasil mengambil list data category!', $result, $meta);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        $validate = $request->validated();

        DB::beginTransaction();
        try {
            $store_id = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

            $result_category = $this->category->store($validate);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat category', $result_category);
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
        $check_category = $this->category->show($id);
        if (!$check_category) return BaseResponse::Notfound("Tidak dapat menemukan data category!");

        return BaseResponse::Ok("Berhasil mengambil detail category!", $check_category);
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
    public function update(CategoryRequest $request, string $id)
    {
        $check = $this->category->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data category!");

        $validate = $request->validated();

        if (auth()->user()->hasRole('outlet')) {
            $validate["outlet_id"] = auth()->user()->outlet_id;
        } else if (auth()->user()->hasRole('warehouse')) {
            $validate["warehouse_id"] = auth()->user()->warehouse_id;
        }

        DB::beginTransaction();
        try {
            $this->category->update($id, $validate);

            DB::commit();
            return BaseResponse::Ok('Berhasil update data category', $validate);
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

        $check = $this->category->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data category!");
        if ($check->products_count) return BaseResponse::Notfound("Data masih terikat dalam product!");

        DB::beginTransaction();
        try {
            $this->category->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listCategory(Request $request)
    {
        $sort = $request->sort ?? 'created_at';
        $order = $request->order ?? 'ASC';

        try {
            $payload = [
                "is_delete" => 0
            ];
            if (auth()->user()->hasRole('warehouse')) $payload["warehouse_id"] = auth()->user()->warehouse_id;
            if (auth()->user()->hasRole('outlet')) $payload["outlet_id"] = auth()->user()->outlet_id;
            $sorting = $this->category->sorted($sort, $order);
            $payload['sorting'] = $sorting;

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $data = $this->category->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data category", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
