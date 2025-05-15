<?php

namespace App\Http\Controllers;

use App\Contracts\Interfaces\SettingInterface;
use App\Helpers\BaseResponse;
use App\Http\Requests\SettingRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    private $settingInterface;
    public function __construct(SettingInterface $settingInterface)
    {
        $this->settingInterface = $settingInterface;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = $this->settingInterface->get();
            return BaseResponse::Ok("Berhasil mengambil semua setting", $data);
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

            $settingData = $this->settingInterface->store($data);

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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SettingRequest $request, string $id)
    {
        $setting = $this->settingInterface->show($id);
        if (!$setting) return BaseResponse::Notfound("id tidak ditemukan");

        $settingData = $request->validated();

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $settingData['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;


        DB::beginTransaction();
        try {

            $updatedSetting = $this->settingInterface->update($id, $settingData);

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
        DB::beginTransaction();

        try {
            $setting = $this->settingInterface->show($id);

            $setting->delete();

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus setting', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
