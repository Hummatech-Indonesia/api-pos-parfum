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
            ])->find($id);
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
            ->withCount('auditDetails')
            ->with(['auditDetails', 'auditDetails.details.product' => function ($query) {
                $query->select('id', 'name');
            }, 'user'])
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

                if (!empty($data['status'])) {
                    $query->where('status', $data['status']);
                }

                if (!empty($data["from_date"])) {
                    $query->where('date', '>=', $data["from_date"]);
                }

                if (!empty($data["until_date"])) {
                    $query->where('date', '<=', $data["until_date"]);
                }

                if (!empty($data["min_variant"])) {
                    $query->having('audit_details_count', '>=', $data["min_variant"]);
                }

                if (!empty($data["max_variant"])) {
                    $query->having('audit_details_count', '<=', $data["max_variant"]);
                }

                if (!empty($data['sort_by']) && !empty($data['sort_direction'])) {
                    $allowedSorts = ['name', 'category', 'status', 'created_at', 'updated_at'];
                    $allowedDirections = ['asc', 'desc'];

                    $sortBy = in_array($data['sort_by'], $allowedSorts) ? $data['sort_by'] : 'updated_at';
                    $sortDirection = in_array(strtolower($data['sort_direction']), $allowedDirections)
                        ? strtolower($data['sort_direction'])
                        : 'desc';

                    $query->orderBy($sortBy, $sortDirection);
                } else {
                    $query->orderBy('updated_at', 'desc');
                }
            })
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->withCount('auditDetails')
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

                if (!empty($data['status'])) {
                    $query->where('status', $data['status']);
                }

                if (!empty($data["from_date"])) {
                    $query->where('date', '>=', $data["from_date"]);
                }

                if (!empty($data["until_date"])) {
                    $query->where('date', '<=', $data["until_date"]);
                }

                if (!empty($data["min_variant"])) {
                    $query->having('audit_details_count', '>=', $data["min_variant"]);
                }

                if (!empty($data["max_variant"])) {
                    $query->having('audit_details_count', '<=', $data["max_variant"]);
                }

                if (!empty($data['sort_by']) && !empty($data['sort_direction'])) {
                    $allowedSorts = ['name', 'status', 'created_at', 'updated_at'];
                    $allowedDirections = ['asc', 'desc'];

                    $sortBy = in_array($data['sort_by'], $allowedSorts) ? $data['sort_by'] : 'updated_at';
                    $sortDirection = in_array(strtolower($data['sort_direction']), $allowedDirections)
                        ? strtolower($data['sort_direction'])
                        : 'desc';

                    $query->orderBy($sortBy, $sortDirection);
                } else {
                    $query->orderBy('updated_at', 'desc');
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
