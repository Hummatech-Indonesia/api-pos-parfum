<?php

namespace App\Contracts\Repositories\Auth;

use App\Contracts\Interfaces\Auth\UserInterface;
use App\Contracts\Repositories\BaseRepository;
use App\Models\Outlet;
use App\Models\User;

class UserRepository extends BaseRepository implements UserInterface
{

    public function __construct(User $user)
    {
        $this->model = $user;
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
        $role = null;
        $warehouse = null;
        $outlet = null;

        if (isset($data["_token"])) unset($data["_token"]);
        try {
            $role = $data["role"];
            unset($data["role"]);

            $role = str_replace(["[", "]", "'"], "", $role);
            $role = explode(",", $role);
        } catch (\Throwable $th) {
            $role = [];
        }

        $orderBy = $data['order_by'] ?? 'created_at';
        $orderDirection = $data['order_direction'] ?? 'desc';

        unset($data['order_by'], $data['order_direction']);

        return $this->model->query()
            ->where('id', '!=', auth()->id())
            ->select('id', 'name', 'email', 'created_at')
            ->with([
                'roles:name',
            ])
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["warehouse"])) {
                    if ($data["warehouse"] == "false") {
                        $query->whereDoesntHave("warehouse");
                    } else if ($data["warehouse"] == "true") {
                        $query->whereHas('warehouse');
                    }

                    $warehouse = $data["warehouse"];
                    unset($data["warehouse"]);

                    if (isset($data["user_id"])) {
                        $query->orWhere("user_id", $data["user_id"]);
                        unset($data["user_id"]);
                    }
                }

                if (isset($data["outlet"])) {
                    if ($data["outlet"] == "false") {
                        $query->whereDoesntHave("outlet");
                    } else if ($data["outlet"] == "true") {
                        $query->whereHas('outlet');
                    }

                    $outlet = $data["outlet"];
                    unset($data["outlet"]);

                    if (isset($data["user_id"])) {
                        $query->orWhere("user_id", $data["user_id"]);
                        unset($data["user_id"]);
                    }
                }

                if (isset($data["user_id"])) {
                    $query->whereIn('id', $data["user_id"]);
                    unset($data["user_id"]);
                }

                foreach ($data as $index => $value) {
                    $query->where($index, $value);
                }
            })
            ->when($role, function ($query) use ($role) {
                $query->role($role);
            })
            ->orderBy($orderBy, $orderDirection);
    }

    public function customQueryV2(array $data): mixed
    {
        $role = null;
        $warehouse = null;
        $outlet = null;

        if (isset($data["_token"])) unset($data["_token"]);
        try {
            $role = $data["role"];
            unset($data["role"]);

            $role = str_replace(["[", "]", "'"], "", $role);
            $role = explode(",", $role);
        } catch (\Throwable $th) {
            $role = [];
        }

        return $this->model->query()
            ->where('id', '!=', auth()->id())
            ->with('store', 'related_store', 'roles', 'warehouse', 'outlet')
            ->when(count($data) > 0, function ($query) use ($data) {
                if (isset($data["warehouse"])) {
                    if ($data["warehouse"] == "false") {
                        $query->whereDoesntHave("warehouse");
                    } else if ($data["warehouse"] == "true" && !isset($data["warehouse_id"])) {
                        $query->whereHas('warehouse');
                    }

                    $warehouse = $data["warehouse"];
                    unset($data["warehouse"]);

                    if (isset($data["user_id"])) {
                        $query->orWhere("user_id", $data["user_id"]);
                        unset($data["user_id"]);
                    }
                }

                if (isset($data["outlet"])) {
                    if ($data["outlet"] == "false") {
                        $query->whereDoesntHave("outlet");
                    } else if ($data["outlet"] == "true" && !isset($data["outlet_id"])) {
                        $query->whereHas('outlet');
                    }

                    $outlet = $data["outlet"];
                    unset($data["outlet"]);

                    if (isset($data["user_id"])) {
                        $query->orWhere("user_id", $data["user_id"]);
                        unset($data["user_id"]);
                    }
                }

                if (isset($data["user_id"])) {
                    $query->whereIn('id', $data["user_id"]);
                    unset($data["user_id"]);
                }

                foreach ($data as $index => $value) {
                    if ($index == "warehouse_id" && $warehouse == "true") {
                        $query->where(function ($q) use ($value) {
                            $q->whereDoesntHave("warehouse")->orWhere('warehouse_id', $value);
                        });
                    } else if ($index == "outlet_id" && $outlet == "true") {
                        $query->where(function ($q) use ($value) {
                            $q->whereDoesntHave("outlet")->orWhere('outlet_id', $value);
                        });
                    } else {
                        $query->where($index, $value);
                    }
                }
            })
            ->when($role, function ($query) use ($role) {
                $query->role($role);
            });
    }

    public function customPaginate(int $pagination = 10, int $page = 1, ?array $data): mixed
    {
        $role = null;
        $search = null;
        if (isset($data["_token"])) unset($data["_token"]);
        if (isset($data["page"])) unset($data["page"]);
        if (isset($data["per_page"])) unset($data["per_page"]);
        try {
            if (isset($data["role"])) {
                $role = $data["role"];
                unset($data["role"]);
            }

            if (isset($data["search"])) {
                $search = $data["search"];
                unset($data["search"]);
            }

            $role = str_replace(["[", "]", "'"], "", $role);
            $role = explode(",", $role);
        } catch (\Throwable $th) {
        }

        $orderBy = $data['order_by'] ?? 'created_at';
        $orderDirection = $data['order_direction'] ?? 'desc';
        unset($data['order_by'], $data['order_direction']);

        $isDelete = $data['is_delete'] ?? null;
        unset($data['is_delete']);

        return $this->model->query()
            ->where('id', '!=', auth()->id())
            ->when(isset($data['store_id']), function ($q) use ($data) {
                $q->where('store_id', $data['store_id']);
            })
            ->when(isset($data['warehouse_id']), function ($q) use ($data) {
                $q->where('warehouse_id', $data['warehouse_id']);
            })
            ->when(isset($data['outlet_id']), function ($q) use ($data) {
                $q->where('outlet_id', $data['outlet_id']);
            })
            ->with([
                'roles',
            ])
            ->when(!is_null($isDelete), function ($query) use ($isDelete) {
                $query->where('is_delete', $isDelete);
            }, function ($query) {
                $query->where('is_delete', 0); // default ke user aktif
            })
            ->when($data, function ($query) use ($data, $search) {
                if ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }

                if (!empty($data["start_date"])) {
                    $query->whereDate('created_at', '>=', $data["start_date"]);
                }

                if (!empty($data["end_date"])) {
                    $query->whereDate('created_at', '<=', $data["end_date"]);
                }
            })
            ->when($role, function ($query) use ($role) {
                $query->role($role);
            })
            ->orderBy($orderBy, $orderDirection)
            ->paginate($pagination, ['*'], 'page', $page)
            ->withQueryString();
    }

    public function show(mixed $id): mixed
    {
        return $this->model
            ->with([
                'store',
                'related_store',
                'warehouse',
            ])
            ->find($id);
    }

    public function checkUserActive(mixed $id): mixed
    {
        return $this->model->with('store', 'related_store', 'roles')->where('is_delete', 0)->find($id);
    }

    public function update(mixed $id, array $data): mixed
    {
        return $this->show($id)->update($data);
    }

    public function delete(mixed $id): mixed
    {
        return $this->show($id)->update(['is_delete' => 1]);
    }

    public function countRetailByStore(string $storeId): int
    {
        return Outlet::where('store_id', $storeId)->where('is_delete', 0)->count();
    }

    public function countOutletUsers(string $storeId, string $outletId): int
    {
        return User::where('store_id', $storeId)->where('outlet_id', $outletId)->count();
    }

    public function mappingExcel($data): mixed 
    {
        $mapping = [];
        $mapping = [
            ['ID', 'Name', 'Email', 'Role']
        ];

        foreach ($data as $item) {
            $mapping[] = [
                'ID' => $item->id,
                'Name' => $item->name,
                'Email' => $item->email,
                'Role' => $item->roles[0]->name ?? 'N/A',
            ];
        }

        return $mapping;
    }
}
