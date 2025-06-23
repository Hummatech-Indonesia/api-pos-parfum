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

        return $this->model->query()
            ->with('store', 'related_store', 'roles', 'warehouse', 'outlet')
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
            });
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

        return $this->model->query()
            ->with('store', 'related_store', 'roles', 'warehouse', 'outlet')
            ->when(count($data) > 0, function ($query) use ($data, $search) {
                if ($search && $search != '') {
                    $query->where(function ($query2) use ($search) {
                        $query2->where('name', 'like', '%' . $search . '%')
                            ->orwhere('email', 'like', '%' . $search . '%');
                    });
                }
                // dd($data);
                foreach ($data as $index => $value) {
                    if ($value && $value != "") $query->where($index, $value);
                }
            })
            ->where('is_delete', 0)
            ->when($role, function ($query) use ($role) {
                $query->role($role);
            })
            ->paginate($pagination, ['*'], 'page', $page)
            ->withQueryString();
    }

    public function show(mixed $id): mixed
    {
        return $this->model->with('store', 'related_store', 'roles', 'outlet', 'warehouse')->find($id);
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
}
