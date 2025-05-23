<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductBundlingRequest;
use App\Contracts\Repositories\Master\ProductBundlingRepository;
use illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductBundlingController extends Controller
{
    protected ProductBundlingRepository $repository;

    public function __construct(ProductBundlingRepository $repository)
    {
        $this->repository = $repository;
    }

    public function store(ProductBundlingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->repository->store($data);
        return response()->json(['message' => 'Product bundling berhasil dibuat', 'data' => $result]);
    }

    public function update(ProductBundlingRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $result = $this->repository->update($id, $data);
        return response()->json(['message' => 'Product bundling berhasil diupdate', 'data' => $result]);
    }

        public function destroy(string $id): JsonResponse
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Product bundling berhasil dihapus']);
    }

    public function restore(string $id): JsonResponse
    {
        $result = $this->repository->restore($id);
        return response()->json(['message' => 'Product bundling berhasil direstore', 'data' => $result]);
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->has('paginate') && $request->paginate == 1) {
            $perPage = $request->input('per_page', 10);
            $result = $this->repository->paginate($perPage);

            return response()->json($result); // penting: jangan bungkus di 'data'
        } else {
            $result = $this->repository->get();

            return response()->json([
                'message' => 'Daftar product bundling berhasil diambil',
                'data' => $result
            ]);
        }
    }

}
