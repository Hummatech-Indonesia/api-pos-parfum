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
            $data = $this->productBlend->customPaginate($per_page, $page, $payload)->toArray();
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

            // Validasi total used stock tidak boleh melebihi result stock
            foreach ($data['product_blend'] as $blend) {
                $totalUsed = collect($blend['product_blend_details'])->sum('used_stock');
                if ($totalUsed > $blend['result_stock']) {
                    return BaseResponse::Error("Total used stock melebihi result stock", null, 422);
                }
            }

            // Upload image jika ada (opsional)
            $image = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $image = $request->file('image')->store('public/product');
            }

            // Simpan produk utama hasil blend
            $product = $this->product->store([
                'store_id' => auth()->user()->store_id,
                'name' => $data['name'],
                // 'image' => $image,
                // 'unit_type' => optional($data['product_blend'][0])['unit_type'],
            ]);

            $product_id = $product->id;

            foreach ($data['product_blend'] as $productBlend) {
                // Simpan product_blend (hasil blending)
                $storeBlend = [
                    'store_id' => auth()->user()->store_id,
                    'warehouse_id' => auth()->user()->warehouse_id,
                    'result_stock' => $productBlend['result_stock'],
                    'product_detail_id' => $productBlend['product_detail_id'],
                    'product_id' => $product_id,
                    'date' => now(),
                    'description' => $productBlend['description'],
                ];

                $blend = $this->productBlend->store($storeBlend);
                $product_blend_id = $blend->id;

                // Simpan detail bahan baku dan kurangi stoknya
                foreach ($productBlend['product_blend_details'] as $blendDetail) {
                    $stock = $this->productStock->checkStock($blendDetail['product_detail_id']);

                    if (!$stock) {
                        // Jika tidak ada record stok, buat 0 terlebih dahulu
                        $stock = $this->productStock->store([
                            'outlet_id' => auth()->user()->outlet_id,
                            'warehouse_id' => auth()->user()->warehouse_id,
                            'product_detail_id' => $blendDetail['product_detail_id'],
                            'stock' => 0,
                        ]);
                    }

                    if ($stock->stock < $blendDetail['used_stock']) {
                        DB::rollBack();
                        return BaseResponse::Error("Stok bahan tidak cukup untuk produk detail ID {$blendDetail['product_detail_id']}", null);
                    }

                    // Kurangi stok bahan baku
                    $stock->stock -= $blendDetail['used_stock'];
                    $stock->save();

                    // Simpan detail blending
                    $this->productBlendDetail->store([
                        'product_blend_id' => $product_blend_id,
                        'product_detail_id' => $blendDetail['product_detail_id'],
                        'used_stock' => $blendDetail['used_stock'],
                        // 'unit_id' => $productBlend['unit_id'],
                    ]);
                }

                // Simpan detail produk hasil blend
                $detail = $this->productDetail->store([
                    'product_id' => $product_id,
                    // 'category_id' => $productBlend['category_id'] ?? null,
                    // 'price' => $productBlend['price'] ?? 0,
                ]);

                // Tambahkan stok hasil blending ke detail produk baru
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
            return BaseResponse::Ok("Berhasil melakukan pencampuran produk", ['product_id' => $product_id]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error("Gagal mencampur produk: " . $th->getMessage(), null);
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

        return BaseResponse::Ok("Berhasil mengambil detail produk blend!", $result['data']);
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
