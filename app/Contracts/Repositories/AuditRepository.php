<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\AuditInterface;
use App\Models\Audit;
use Illuminate\Database\QueryException;

class AuditRepository extends BaseRepository implements AuditInterface
{
    public function __construct(Audit $audit)
    {
        $this->model = $audit;
    }

    public function get(): mixed
    {
        return $this->model->all();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->query()->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->model->find($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->delete();
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with( 'details')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                if (!empty($data['name'])) {
                    $query->where('name', 'like', '%' . $data['name'] . '%');
                }

                // Filter berdasarkan status
                if (!empty($data['status'])) {
                    $query->where('status', $data['status']);
                }

                // Filter berdasarkan rentang tanggal
                if (!empty($data['date'])) {
                    $query->where('date', $data['date']);
                }

                // foreach ($data as $index => $value) {
                //     $query->where($index, $value);
                // }
            })
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
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

    public function allDataTrashed(array $filter = []): mixed // Untuk mencari data yang dihapus
    {
        return $this->model->onlyTrashed()
            ->when(!empty($filter), function ($query) use ($filter) {
                foreach ($filter as $key => $value) {
                    $query->where($key, $value);
                }
            })
            ->get();
    }

    public function restore(string $id): Audit
    {
        $audit = $this->model->withTrashed()->findOrFail($id);
        $audit->restore();

        // Restore juga semua AuditDetail yang terhapus
        $audit->details()->withTrashed()->get()->each(function ($detail) {
            $detail->restore();
        });

        return $audit;
    }
}
