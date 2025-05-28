<?php

namespace App\Http\Controllers;

use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\ProductBlendInterface;
use App\Helpers\BaseResponse;
use App\Http\Requests\ProductBlendRequest;
use App\Models\ProductBlend;
use App\Models\ProductStock;
use App\Services\ProductBlendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductBlendController extends Controller
{
    private ProductBlendService $productBlendService;
    private ProductBlendInterface $productBlend;
    private ProductInterface $product;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;

    public function __construct(ProductBlendService $productBlendService, ProductBlendInterface $productBlend, ProductInterface $product, ProductDetailInterface $productDetail, ProductStockInterface $productStock)
    {
        $this->productBlendService = $productBlendService;
        $this->productBlend = $productBlend;
        $this->product = $product;
        $this->productDetail = $productDetail;
        $this->productStock = $productStock;
    }

    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductBlendRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $this->productBlendService->store($request);

            // Simpan produk baru ke tabel products
            $product = $this->product->store($this->productBlendService->storeProduct($request));
            $product_id = $product->id;

            // Simpan data ke tabel product_blends
            $blend = $this->productBlend->store($data['store_product_blend']);
            $data['product_blend_id'] = $blend->id;

            // Simpan detail bahan dan kurangi stok
            foreach ($data['product_blend'] as $productBlend) {
                foreach ($productBlend['product_blend_details'] as $blendDetail) {
                    $stock = $this->productStock->checkStock($blendDetail['product_detail_id']);
                    if(!$stock) {
                        $this->productStock->store([
                            'warehouse_id' => auth()->user()->warehouse_id,
                            'product_detail_id' => $blendDetail['product_detail_id'],
                        ]);
                    }
                    if ($stock && $stock->stock < $blendDetail['used_stock']) {
                        DB::rollBack();
                        return BaseResponse::Error("Stok bahan tidak cukup", null);
                    }

                    $this->productBlendService->storeBlendDetail([
                        'product_blend' => [$productBlend],
                        'product_blend_id' => $data['product_blend_id']
                    ]);
                }
            }

            // Simpan hasil blend ke product_detail dan catat stoknya
            foreach ($data['product_blend'] as $productBlend) {
                $detail = $this->productDetail->store([
                    'product_id' => $product_id,
                    'category_id' => $productBlend['category_id'] ?? null,
                    'price' => $productBlend['price'] ?? 0,
                ]);

                // $stock = $this->productStock->checkStock($blendDetail['product_detail_id']);
                $stock = $this->productStock->checkNewStock($productBlend['product_detail_id'], $product_id);
                if(!$stock) {
                    $stock = $this->productStock->store([
                        'warehouse_id' => auth()->user()->warehouse_id,
                        'product_detail_id' => $detail->id,
                        'product_id' => $product_id,
                        'stock' => 0,
                    ]);
                }
                // $stock = $this->productStock->getFromProductDetailOrNew($productBlend['product_detail_id'], $product_id);

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

    //JADI SATU FLOW 
    //DIKASI FILTER
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!Str::isUuid($id)) {
            return BaseResponse::Error("ID produk blend tidak valid.", null);
        }
        
        $check_product_blend = $this->productBlend->show($id);
        if (!$check_product_blend) return BaseResponse::Notfound("Tidak dapat menemukan data produk blend!");

        return BaseResponse::Ok("Berhasil mengambil detail produk blend!", $check_product_blend);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
