<?php

namespace App\Contracts\Repositories\Transaction;

use App\Contracts\Interfaces\Transaction\ShiftUserInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\ShiftUser;
use Illuminate\Database\QueryException;

class ShiftUserRepository extends BaseRepository implements ShiftUserInterface
{
    public function __construct(ShiftUser $shift)
    {
        $this->model = $shift;
    }

    public function get(): mixed
    {
        return $this->model->get();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('store')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                if (!empty($data["from_date"])) {
                    $query->where('date', '>=', $data["from_date"]);
                }

                if (!empty($data["until_date"])) {
                    $query->where('date', '<=', $data["until_date"]);
                }
                foreach ($data as $index => $value) {
                    if (in_array($index, ['from_date', 'until_date'])) continue;
                    $query->where($index, $value);
                }
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function getDataForExport(array $filters)
    {

        $query = ShiftUser::query()
            ->with(['user', 'outlet', 'store'])
            ->when($filters, function ($query) use ($filters) {
                if (!empty($filters["search"])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('date', 'like', '%' . $filters["search"] . '%');
                    });
                }

                if (!empty($filters['start_date'])) {
                    $query->whereDate('date', '>=', $filters['start_date']);
                }

                if (!empty($filters['end_date'])) {
                    $query->whereDate('date', '<=', $filters['end_date']);
                }
            });
            // dd($query->get());
            return $query->get();
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store')->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }
}
