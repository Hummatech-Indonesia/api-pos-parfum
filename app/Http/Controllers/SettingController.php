<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\SettingRequest;
use App\Contracts\Repositories\SettingRepository;
use App\Models\Setting;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SettingController extends Controller
{
    private $settingRepository;
    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 8;
        $page = $request->page ?? 1;
        $payload = $request->only(['search', 'name']);

        $data['user_id'] = auth()?->user()?->id;

        // check query filter
        if ($request->search) $payload["search"] = $request->search;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        try {
            $data =  $this->settingRepository->customPaginate($per_page, $page, $payload)->toArray();

            $result = $data["data"];
            unset($data["data"]);

            return BaseResponse::Paginate("Berhasil mengambil semua setting", $result, $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), data: null);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SettingRequest $request)
    {
        $data = $request->validated();

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $data['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        DB::beginTransaction();

        try {

            $settingData = $this->settingRepository->store($data);

            DB::commit();

            return BaseResponse::Ok('Berhasil menambahkan setting', $settingData);
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
        try {
            $setting = $this->settingRepository->show($id);

            if (!$setting) {
                return BaseResponse::Notfound("Setting tidak ditemukan");
            }

            return BaseResponse::Ok("Berhasil mengambil detail setting", $setting);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Terjadi kesalahan: " . $th->getMessage(), null);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SettingRequest $request, string $id)
    {
        $setting = $this->settingRepository->show($id);
        if (!$setting) return BaseResponse::Notfound("setting tidak ditemukan");

        $settingData = $request->validated();

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $settingData['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        DB::beginTransaction();
        try {

            $updatedSetting = $this->settingRepository->update($id, $settingData);

            DB::commit();
            return BaseResponse::Ok('Berhasil memperbarui setting', $updatedSetting);
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
        $setting = $this->settingRepository->show($id);
        if (!$setting) return BaseResponse::Notfound("setting tidak ditemukan");
        DB::beginTransaction();

        try {

            $setting->delete();

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus setting', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listWSetting(Request $request)
    {
        try {
            $payload = [];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $data = $this->settingRepository->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data setting", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function trashed(Request $request)
    {
        try {
            $payload = [];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

            $data = $this->settingRepository->allDataTrashed($payload);


            return BaseResponse::Ok("Berhasil mengambil data sampah setting", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function restore(string $id)
    {
        $setting = Setting::withTrashed()->find($id);
        if (!$setting) return BaseResponse::Notfound("sampah setting tidak ditemukan");
        try {
            $setting = $this->settingRepository->restore($id);
            return BaseResponse::Ok("setting berhasil dikembalikan", $setting);
        } catch (\Throwable $th) {
            return BaseResponse::Error("Gagal mengembalikan setting: " . $th->getMessage(), null);
        }
    }
}
