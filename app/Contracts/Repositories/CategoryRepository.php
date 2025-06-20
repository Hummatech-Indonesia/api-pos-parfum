<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\CategoryInterface;
use App\Models\Category;
use Illuminate\Database\QueryException;

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

    public function customQuery(array $data): mixed
    {
        return $this->model->query()
            ->with('store')
            ->withCount(['products' => function ($q) {
                $q->where('is_delete', 0);
            }])
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('store:id,name')
            ->withCount(['products' => function ($q) {
                $q->where('is_delete', 0);
            }])
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
        $update = $this->model->find($id);

        if (!$update || $update->is_delete) return null;

        $update->update($data);
        return $update;
    }

    public function delete(mixed $id): mixed
    {
        $delete = $this->model->find($id);

        if (!$delete || $delete->is_delete) return null;

        $delete->update(["is_delete" => 1]);
        return $delete;
    }
}
