<?php

namespace App\Http\Controllers\Transaction;

use App\Contracts\Interfaces\Transaction\ShiftUserInterface;
use App\Contracts\Repositories\Transaction\ShiftUserRepository;
use App\Exports\ShiftUserExport;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ShiftUserRequest;
use App\Http\Requests\Transaction\ShiftUserSyncRequest;
use App\Http\Resources\ShiftResource;
use App\Models\ShiftUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use function PHPUnit\Framework\isEmpty;

class ShiftUserController extends Controller
{
    private ShiftUserInterface $shiftUser;
    private ShiftUserRepository $shiftUserRepository;

    public function __construct(ShiftUserInterface $shiftUser, ShiftUserRepository $shiftUserRepository)
    {
        $this->shiftUser = $shiftUser;
        $this->shiftUserRepository = $shiftUserRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [];

        if ($request->has('from_date')) {
            $payload['from_date'] = Carbon::createFromFormat('d-m-Y', $request->from_date)->format('Y-m-d');
        }

        if ($request->has('until_date')) {
            $payload['until_date'] = Carbon::createFromFormat('d-m-Y', $request->until_date)->format('Y-m-d');
        }

        if ($request->has('date')) {
            $payload['from_date'] = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
            $payload['until_date'] = $payload['from_date'];
        }

        Carbon::parse($request->form_date)->format('d-m-Y');

        // check query filter
        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        if (auth()?->user()?->outlet?->id || auth()?->user()?->outlet_id) $payload['outlet_id'] = auth()?->user()?->outlet?->id ?? auth()?->user()?->outlet_id;

        try {
            $data = $this->shiftUser->customPaginate($per_page, $page, $payload);
            $resource = ShiftResource::collection($data);
            $meta = PaginationHelper::meta($data);

            $result = $data["data"];
            unset($data["data"]);

            return BaseResponse::Paginate('Berhasil mengambil list data shift!', $resource, $meta);
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
    public function store(ShiftUserRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not 
            $data["user_id"] = auth()->user()->id;
            $data["store_id"] = auth()->user()?->store?->id ?? auth()->user()?->store_id;
            $data["outlet_id"] = auth()->user()?->outlet?->id ?? auth()->user()?->outlet_id;
            $result = $this->shiftUser->store($data);

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat warehouse', $result);
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
        $check = $this->shiftUser->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        return BaseResponse::Ok("Berhasil mengambil detail warehouse!", $check);
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
    public function update(ShiftUserRequest $request, string $id)
    {
        $data = $request->validated();

        $check = $this->shiftUser->show($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data warehouse!");

        DB::beginTransaction();
        try {
            $data["user_id"] = auth()->user()->id;
            $result = $check->update($data);

            DB::commit();
            return BaseResponse::Ok('Berhasil update data warehouse', $result);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {}

    /**
     * Display a listing of the resource.
     */
    public function getData(Request $request)
    {
        $payload = [];

        // check query filter
        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        try {
            $data = $this->shiftUser->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data shift", $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function syncStoreData(ShiftUserSyncRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // check has data user or not
            foreach ($data["shift"] as $item) {
                $item["store_id"] = auth()->user()?->store?->id ?? auth()->user()?->store_id;
                $item["outlet_id"] = auth()->user()?->outlet?->id ?? auth()->user()?->outlet_id;
                $this->shiftUser->store($item);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil sinkronisasi data shift', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function export(Request $request)
    {
        $filters = [];

        if ($request->from_date) {
            $filters['from_date'] = Carbon::createFromFormat('d-m-Y', $request->from_date)
                ->format('Y-m-d');
        }

        if ($request->until_date) {
            $filters['until_date'] = Carbon::createFromFormat('d-m-Y', $request->until_date)
                ->format('Y-m-d');
        }

        try {
            return Excel::download(new ShiftUserExport($filters), 'shift_user.xlsx');
        } catch (\Throwable $th) {
            return BaseResponse::ServerError($th->getMessage(), null);
        }
    }

    public function exportPdf(Request $request)
    {
        $filters = [];

        if ($request->search) $filters["search"] = $request->search;
        if ($request->start_date) $filters["start_date"] = $request->start_date;
        if ($request->end_date) $filters["end_date"] = $request->end_date;

        try {
            $shiftUsers = $this->shiftUserRepository->getDataForExport($filters);
             $pdf = Pdf::loadView('exports.shift_user', compact('shiftUsers'))
                  ->setPaper('4A', 'landscape');

            return $pdf->download('shift_user.pdf');
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
