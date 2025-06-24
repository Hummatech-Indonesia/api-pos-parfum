<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Master\ProductService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Master\ProductRequest;
use App\Services\Master\ProductDetailService;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductVarianInterface;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    private ProductInterface $product;
    private ProductDetailInterface $productDetail;
    private ProductService $productService;
    private ProductVarianInterface $productVarian;
    private CategoryInterface $category;
    private ProductStockInterface $productStock;
    private ProductDetailService $productDetailService;

    public function __construct(
        ProductInterface $product,
        ProductDetailInterface $productDetail,
        ProductService $productService,
        ProductVarianInterface $productVarian,
        CategoryInterface $category,
        ProductStockInterface $productStock,
        ProductDetailService $productDetailService
    ) {
        $this->product = $product;
        $this->productDetail = $productDetail;
        $this->productVarian = $productVarian;
        $this->category = $category;
        $this->productService = $productService;
        $this->productStock = $productStock;
        $this->productDetailService = $productDetailService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        $payload = [
            "is_delete" => $request->get('is_delete', 0),
        ];

        if ($request->search) {
            $payload["search"] = $request->search;
        }

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
            $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        }

        $payload["sort_by"] = in_array($request->sort_by, ['name', 'created_at']) ? $request->sort_by : null;
        $payload["sort_order"] = in_array($request->sort_order, ['asc', 'desc']) ? $request->sort_order : 'asc';

        $paginated = $this->product->customPaginate($perPage, $page, $payload);
        $paginated->load('details.category', 'category');

        $resource = ProductResource::collection($paginated);

        return BaseResponse::Paginate('Berhasil mengambil list data product !', $resource->collection, $paginated->toArray());
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $data["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $mapProduct = $this->productService->dataProduct($data);

            $result_product = $this->product->store($mapProduct);

            foreach ($data["product_details"] as $detail) {
                $detail["product_id"] = $result_product->id;
                $detail["category_id"] = $data["category_id"];

                /**
                 * Pengecekan apakah data varian yang dikirim sudah ada atau belum
                 */
                if (isset($detail["product_varian_id"])) {
                    $check_varian = $this->productVarian->customQuery(["id" => $detail["product_varian_id"], "store_id" => $data["store_id"]])->first();
                    if (!$check_varian) {
                        /**
                         * Check varian name has owned in this store
                         */
                        $checkVarianName = $this->productVarian->customQuery(["name" => $detail["product_varian_id"], "store_id" => $data["store_id"]])->first();
                        if (!$checkVarianName) {
                            $this->productVarian->store(["name" => $detail["product_varian_id"], "store_id" => $data["store_id"]]);
                            $store_varian = $this->productVarian->customQuery(["name" => $detail["product_varian_id"], "store_id" => $data["store_id"]])->first();
                            $detail["product_varian_id"] = $store_varian->id;
                        } else {
                            $detail["product_varian_id"] = $checkVarianName?->id;
                        }
                    }
                }

                $mappingDetail = $this->productDetailService->dataProductDetail($detail);
                $storedDetail = $this->productDetail->store($mappingDetail);

                $this->productStock->store([
                    'warehouse_id' => auth()->user()->warehouse_id,
                    'outlet_id' => auth()->user()->outlet_id ?? null,
                    'product_id' => $result_product->id,
                    'product_detail_id' => $storedDetail->id,
                    'stock' => $storedDetail->stock ?? 0,
                ]);
            }

            DB::commit();
            return BaseResponse::Ok('Berhasil membuat product ', $result_product->load(['details']));
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = $this->product->checkActiveWithDetailV2($id);

        if (!$product) {
            return BaseResponse::Notfound("Tidak dapat menemukan data product !");
        }

        $product->loadMissing(['details.productStockWarehouse', 'details.productStockOutlet', 'details.category', 'details.category']);

        return BaseResponse::Ok("Berhasil mengambil detail product !", new ProductResource($product));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, string $id)
    {

        $data = $request->validated();
        DB::beginTransaction();
        try {
            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $data["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $select_product = $this->product->show($id);

            $mapProduct = $this->productService->dataProductUpdate($data, $select_product);
            $this->product->update($id, $mapProduct);
            $products = $select_product->details->where('is_delete', 0);

            foreach ($data["product_details"] as $detail) {
                $detail["product_id"] = $id;
                $detail["category_id"] = $data["category_id"];

                /**
                 * Pengecekan apakah data varian yang dikirim sudah ada atau belum
                 */
                if (isset($detail["product_varian_id"])) {
                    $check_varian = $this->productVarian->customQuery(["id" => $detail["product_varian_id"], "store_id" => $data["store_id"]])->first();
                    if (!$check_varian) {
                        /**
                         * Check varian name has owned in this store
                         */
                        $checkVarianName = $this->productVarian->customQuery(["name" => $detail["product_varian_id"], "store_id" => $data["store_id"]])->first();
                        if (!$checkVarianName) {
                            $this->productVarian->store(["name" => $detail["product_varian_id"], "store_id" => $data["store_id"]]);
                            $store_varian = $this->productVarian->customQuery(["name" => $detail["product_varian_id"], "store_id" => $data["store_id"]])->first();
                            $detail["product_varian_id"] = $store_varian->id;
                        } else {
                            $detail["product_varian_id"] = $checkVarianName?->id;
                        }
                    }
                }

                if (isset($detail["product_detail_id"])) {
                    $idDetail = $detail["product_detail_id"];
                    $products = collect($products)->filter(function ($item) use ($detail) {
                        return $item->id != $detail["product_detail_id"];
                    });

                    unset($detail["product_detail_id"]);
                    $productDetailShow = $this->productDetail->show($idDetail);
                    $mappingDetailUpdate = $this->productDetailService->dataProductDetailUpdate($detail, $productDetailShow);
                    $this->productDetail->update($idDetail, $mappingDetailUpdate);
                } else {
                    $mappingDetail = $this->productDetailService->dataProductDetail($detail);
                    $this->productDetail->store($detail);
                }
            }

            foreach ($products as $product_detail) {
                $product_detail->is_delete = true;
                $product_detail->save();
            }

            $result_product = $this->product->checkActiveWithDetail($id);
            DB::commit();
            return BaseResponse::Ok('Berhasil update product', $result_product);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $check = $this->product->checkActive($id);
        if (!$check) return BaseResponse::Notfound("Tidak dapat menemukan data product !");

        DB::beginTransaction();
        try {
            $this->product->delete($id);

            DB::commit();
            return BaseResponse::Ok('Berhasil menghapus data', null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listProduct(Request $request)
    {
        try {
            $payload = [
                'is_delete' => $request->get('is_delete', 0),
                'search' => $request->get('search'),
                'sort_by' => in_array($request->sort_by, ['name', 'created_at']) ? $request->sort_by : null,
                'sort_order' => in_array($request->sort_order, ['asc', 'desc']) ? $request->sort_order : 'desc',
            ];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
                $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            }

            $products = $this->product->getListProduct($payload);

            return BaseResponse::Ok("Berhasil mengambil data product", ProductResource::collection($products));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }



}
