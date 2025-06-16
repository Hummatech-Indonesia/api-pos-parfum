<?php

namespace App\Contracts\Repositories\Master;

use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\DiscountVoucher;
use App\Models\Product;

class DiscountVoucherRepository extends BaseRepository implements DiscountVoucherInterface
{

    public function __construct(DiscountVoucher $discountVoucher)
    {
        $this->model = $discountVoucher;
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
            ->with('store', 'details', 'details.varian')
            ->where('is_delete', 0)
            ->when(count($data) > 0, function ($query) use ($data) {
                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        return $this->model->query()
            ->with('store', 'details', 'details.varian')
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
        return $this->model->with(['store' => function ($query) {
            $query->select('id', 'name');
        }, 'details', 'details.varian' => function ($query) {
            $query->select('id', 'name');
        }])->find($id);
    }

    public function checkActive(mixed $id): mixed
    {
        return $this->model->with('store', 'details', 'details.varian')->where('is_delete', 0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        $model = $this->model->select('id')->findOrFail($id);
        $model->update($data);

        return $this->show($id);
    }

    public function delete(mixed $id): mixed
    {
        return $this->model->select('id')->show($id)->update(["is_delete" => 1]);
    }
}
