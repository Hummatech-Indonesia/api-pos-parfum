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

    public function show(mixed $id): ?Audit
    {
        return $this->model->query()
            ->with([
                'auditDetails',
                'auditDetails.unit' => function ($query) {
                    $query->select('id', 'name');
                },
                'auditDetails.productDetail',
                'outlet' => function ($query) {
                    $query->select('id', 'name');
                },
                'store' => function ($query) {
                    $query->select('id', 'name');
                },
                'auditDetails.details.product' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
            ->find($id);
    }



    public function update(mixed $id, array $data): mixed
    {
        $this->model->select('id')->findOrFail($id)->update($data);

        return $this->show($id);
    }

    public function delete(mixed $id): mixed
    {
        $audit = $this->model->select('id')->find($id);

        if (!$audit) {
            return false;
        }

        $audit->details()->delete();

        return $audit->delete();
    }

    public function customPaginate(int $pagination = 8, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with(['auditDetails', 'auditDetails.details.product' => function ($query) {
                $query->select('id', 'name');
            }])
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
            })->with(['auditDetails', 'auditDetails.details.product' => function ($query) {
                $query->select('id', 'name');
            }]);
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
        $audit = $this->model->select('id', 'name')->withTrashed()->findOrFail($id);
        $audit->restore();

        // Restore juga semua AuditDetail yang terhapus
        $audit->details()->withTrashed()->get()->each(function ($detail) {
            $detail->restore();
        });

        return $audit;
    }
}
