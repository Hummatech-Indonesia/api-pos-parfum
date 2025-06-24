<?php

namespace App\Contracts\Repositories;

use App\Models\Category;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use App\Contracts\Interfaces\CategoryInterface;

class CategoryRepository extends BaseRepository implements CategoryInterface
{
    public function __construct(Category $category)
    {
        $this->model = $category;
    }

    public function get(): mixed
    {
        return $this->model->get();
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function sorted($column, $order)
    {
        $data = [
            "column" => "created_at",
            "order" => "ASC",
        ];

        $validColumns = Schema::getColumnListing($this->model->getTable());
        if (in_array($column, $validColumns)) {
            $data['column'] = $column;
        }

        if (in_array($order, ["ASC", "DESC"])) {
            $data['order'] = $order;
        }

        return $data;
    }

    public function customQuery(array $data): mixed
    {
        $sorting = $data['sorting'] ?? [];
        if (isset($data['sorting'])) {
            unset($data['sorting']);
        }

        return $this->model->query()
            // ->with('store')
            ->withCount(['products' => function ($q) {
                $q->where('is_delete', 0);
            }])
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })
            ->select('name', 'created_at')
            ->addSelect(\DB::raw("(select count(*) from products where products.category_id = categories.id and is_delete = 0) as products_count"))
            ->orderBy($sorting['column'] ?? "created_at", $sorting['order'] ?? "ASC");
    }



    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $sorting = $data['sorting'] ?? [];
        if (isset($data['sorting'])) {
            unset($data['sorting']);
        }

        return $this->model->query()
            // ->with('store:id,name')
            // ->withCount(['products' => function ($q) {
            //     $q->where('is_delete', 0);
            // }])
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["search"])) {
                    $query->where(function ($query2) use ($data) {
                        $query2->where('name', 'like', '%' . $data["search"] . '%');
                    });
                    unset($data["search"]);
                }

                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })
            ->select('name', 'created_at')
            ->addSelect(\DB::raw("(select count(*) from products where products.category_id = categories.id and is_delete = 0) as products_count"))
            ->orderBy($sorting['column'] ?? "created_at", $sorting['order'] ?? "ASC")
            ->paginate($pagination, ['*'], 'page', $page);
        // ->appends(['search' => $request->search, 'year' => $request->year]);
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store:id,name')->withCount('products')->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('store:id,name')->withCount('products')->where('is_delete', 0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->model->select('id', 'is_delete')->findOrFail($id);

        if ($model->is_delete) {
            return null;
        }

        $model->update($data);
        return $model->fresh();
    }

    public function delete(mixed $id): mixed
    {
        $model = $this->model->select('id')->findOrFail($id);
        $model->update(['is_delete' => 1]);

        return $model->fresh();
    }
}
