<?php

namespace App\Http\Controllers;

use App\Contracts\Interfaces\Master\{ProductDetailInterface, ProductInterface, ProductStockInterface};
use App\Contracts\Interfaces\{ProductBlendInterface, ProductBlendDetailInterface};
use App\Helpers\BaseResponse;
use App\Http\Requests\ProductBlendRequest;
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

        try {
            $data = $this->productBlend->customPaginate($per_page, $page, $payload, ['productBlendDetails'])->toArray();
            $result = $data['data'];
            unset($data['data']);
            return BaseResponse::Paginate('Berhasil mengambil list data product blend!', $result, $data);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function store(ProductBlendRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            foreach ($data['product_blend'] as $productBlend) {
                $data['store_product_blend'] = [
                    'store_id' => auth()->user()->store_id,
                    'warehouse_id' => auth()->user()->warehouse_id,
                    'result_stock' => $productBlend['result_stock'],
                    'product_detail_id' => $productBlend['product_detail_id'],
                    'unit_id' => $productBlend['unit_id'],
                    'date' => $data['date'] ?? now(),
                ];
            }

            $image = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $image = $request->file('image')->store('public/product');
            }

            $product = $this->product->store([
                'store_id' => auth()->user()->store_id,
                'name' => $data['name'],
                'image' => $image,
                'unit_type' => $data['product_blend'][0]['unit_type'],
            ]);
            $product_id = $product->id;

            $blend = $this->productBlend->store($data['store_product_blend']);
            $data['product_blend_id'] = $blend->id;

            foreach ($data['product_blend'] as $productBlend) {
                foreach ($productBlend['product_blend_details'] as $blendDetail) {
                    $stock = $this->productStock->checkStock($blendDetail['product_detail_id']);

                    if (!$stock) {
                        $this->productStock->store([
                            'outlet_id' => auth()->user()->outlet_id,
                            'warehouse_id' => auth()->user()->warehouse_id,
                            'product_detail_id' => $blendDetail['product_detail_id'],
                        ]);
                    } elseif ($stock->stock < $blendDetail['used_stock']) {
                        DB::rollBack();
                        return BaseResponse::Error("Stok bahan tidak cukup", null);
                    } else {
                        $stock->stock -= $blendDetail['used_stock'];
                        $stock->save();
                    }

                    $this->productBlendDetail->store([
                        'product_blend_id' => $data['product_blend_id'],
                        'product_detail_id' => $blendDetail['product_detail_id'],
                        'used_stock' => $blendDetail['used_stock'],
                        'unit_id' => $productBlend['unit_id'],
                    ]);
                }
            }

            foreach ($data['product_blend'] as $productBlend) {
                $detail = $this->productDetail->store([
                    'product_id' => $product_id,
                    'category_id' => $productBlend['category_id'] ?? null,
                    'price' => $productBlend['price'] ?? 0,
                ]);

                $stock = $this->productStock->checkNewStock($productBlend['product_detail_id'], $product_id);

                if (!$stock) {
                    $stock = $this->productStock->store([
                        'outlet_id' => auth()->user()->outlet_id,
                        'warehouse_id' => auth()->user()->warehouse_id,
                        'product_detail_id' => $detail->id,
                        'product_id' => $product_id,
                        'stock' => 0,
                    ]);
                }

                $stock->stock += $productBlend['result_stock'];
                $stock->save();
            }

            DB::commit();
            return BaseResponse::Ok("Berhasil melakukan pencampuran produk", $data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error("Gagal mencampur produk: " . $th->getMessage(), null);
        }
    }

    public function show(string $id)
    {
        if (!Str::isUuid($id)) {
            return BaseResponse::Error("ID produk blend tidak valid.", null);
        }

        $check_product_blend = $this->productBlend->show($id);
        if (!$check_product_blend) return BaseResponse::Notfound("Tidak dapat menemukan data produk blend!");

        return BaseResponse::Ok("Berhasil mengambil detail produk blend!", $check_product_blend);
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
