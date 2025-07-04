<?php

namespace App\Http\Controllers;

use App\Contracts\Interfaces\Master\{ProductDetailInterface, ProductInterface, ProductStockInterface};
use App\Contracts\Interfaces\{ProductBlendInterface, ProductBlendDetailInterface};
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use App\Http\Requests\ProductBlendRequest;
use App\Http\Resources\ProductBlendResource;
use App\Http\Resources\ProductBlendWithDetailResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductBlendController extends Controller
{
    private ProductBlendInterface $productBlend;
    private ProductInterface $product;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;
    private ProductBlendDetailInterface $productBlendDetail;

    public function __construct(
        ProductBlendInterface $productBlend,
        ProductInterface $product,
        ProductDetailInterface $productDetail,
        ProductStockInterface $productStock,
        ProductBlendDetailInterface $productBlendDetail
    ) {
        $this->productBlend = $productBlend;
        $this->product = $product;
        $this->productDetail = $productDetail;
        $this->productStock = $productStock;
        $this->productBlendDetail = $productBlendDetail;
    }

    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $payload = [];


        if ($request->search) $payload["search"] = $request->search;
        if ($request->date) $payload["date"] = $request->date;
        if ($request->description) $payload["description"] = $request->description;
        if ($request->productDetail) $payload["productDetail"] = $request->productDetail;
        if ($request->start_date) $payload["start_date"] = $request->start_date;
        if ($request->end_date) $payload["end_date"] = $request->end_date;
        if ($request->min_quantity) $payload["min_quantity"] = $request->min_quantity;
        if ($request->max_quantity) $payload["max_quantity"] = $request->max_quantity;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

        try {
            $paginate = $this->productBlend->customPaginate($per_page, $page, $payload);

            $resource = ProductBlendResource::collection($paginate);
            $result = $resource->collection->values();
            $meta = PaginationHelper::meta($paginate);

            return BaseResponse::Paginate('Berhasil mengambil list data product blend!', $result, $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function store(ProductBlendRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $productBlends = [];

            foreach ($data['product_blend'] as $productBlend) {
                $storeBlend = [
                    'store_id' => auth()->user()->store_id,
                    'warehouse_id' => auth()->user()->warehouse_id,
                    'unit_id' => $productBlend['unit_id'],
                    'result_stock' => $productBlend['result_stock'],
                    'product_detail_id' => $productBlend['product_detail_id'],
                    'product_id' => null,
                    'date' => now(),
                    'description' => $productBlend['description'],
                ];

                $blend = $this->productBlend->store($storeBlend);
                $product_blend_id = $blend->id;
                $productBlends[] = $product_blend_id;

                foreach ($productBlend['product_blend_details'] as $blendDetail) {
                    $stock = $this->productStock->checkStock($blendDetail['product_detail_id']);
                    $productDetail = $this->productDetail->find($blendDetail['product_detail_id']);
                    $productName = $productDetail?->product?->name ?? 'Produk tidak ditemukan';

                    if (!$stock || $stock->stock <= 0) {
                        $warehouseName = auth()->user()->warehouse?->name ?? 'Gudang Tidak Dikenal';
                        return BaseResponse::Custom(false, "Stok bahan '$productName' kosong di gudang '$warehouseName'. Tidak dapat melakukan pencampuran.", null, 422);
                    }

                    if ($stock->stock < $blendDetail['used_stock']) {
                        $remainingStock = $stock->stock;
                        DB::rollBack();
                        return BaseResponse::Custom(false, "Stok bahan tidak cukup untuk produk {$productName}, sisa stock {$remainingStock}", null, 422);
                    }

                    // Kurangi stok bahan baku
                    $stock->stock -= $blendDetail['used_stock'];
                    $stock->save();

                    // Simpan detail blending
                    $this->productBlendDetail->store([
                        'product_blend_id' => $product_blend_id,
                        'product_detail_id' => $blendDetail['product_detail_id'],
                        'used_stock' => $blendDetail['used_stock'],
                    ]);
                }

                $detail = $this->productDetail->find($productBlend['product_detail_id']);

                $stock = $this->productStock->getFromProductDetail($productBlend['product_detail_id']);

                if (!$stock) {
                    return BaseResponse::Custom(false, "Produk hasil blend belum memiliki stok di gudang ini.", null, 422);
                }

                $stock->stock += $productBlend['result_stock'];
                $stock->save();
            }

            DB::commit();
            $blends = $this->productBlend->getByIds($productBlends);

            return BaseResponse::Ok("Berhasil melakukan pencampuran produk", ProductBlendResource::collection($blends));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error("Gagal mencampur produk: " . $th->getMessage(), null);
        }
    }

    public function listProductBlend(Request $request)
    {
        try {
            $payload = [];
            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;

            $data = $this->productBlend->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data product blend", ProductBlendResource::collection($data));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function show(string $id)
    {
        $page = request()->get('transaction_page') ?? 1;

        $result = $this->productBlend->getDetailWithPagination($id, $page);

        if (!$result['status']) {
            if ($result['error'] === 'invalid_uuid') {
                return BaseResponse::Error("ID produk blend tidak valid.", null);
            }

            return BaseResponse::Notfound("Tidak dapat menemukan data produk blend!");
        }

        $resource = new ProductBlendWithDetailResource($result['data']);

        return BaseResponse::Ok("Berhasil mengambil detail produk blend!", $resource);
    }

    public function update(Request $request, string $id)
    {
        // Not implemented yet
    }

    public function destroy(string $id)
    {
        // Not implemented yet
    }
}
