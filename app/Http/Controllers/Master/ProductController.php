<?php

namespace App\Http\Controllers\Master;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Enums\UploadDiskEnum;
use App\Helpers\BaseResponse;
use App\Exports\ProductExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PaginationHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\ProductResource;
use App\Services\Master\ProductService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Master\ProductRequest;
use App\Services\Master\ProductDetailService;
use App\Contracts\Interfaces\CategoryInterface;
use App\Contracts\Interfaces\Master\ProductInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductVarianInterface;
use Illuminate\Support\Facades\Log;

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
            'min_price'    => $request->get('min_price'),
            'max_price'    => $request->get('max_price'),
            'min_sales'    => $request->get('min_sales'),
            'max_sales'    => $request->get('max_sales'),
            'min_stock'    => $request->get('min_stock'),
            'max_stock'    => $request->get('max_stock'),
            'category'    => $request->get('category'),
        ];

        if ($request->search) {
            $payload["search"] = $request->search;
        }

        if (auth()->user()->hasRole('warehouse')) $payload["warehouse_id"] = auth()->user()->warehouse_id;
        if (auth()->user()->hasRole('outlet')) $payload["outlet_id"] = auth()->user()->outlet_id;

        if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
            $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
        }


        $paginated = $this->product->customPaginate($perPage, $page, $payload);


        $resource = ProductResource::collection($paginated);
        $meta = PaginationHelper::meta($paginated);


        return BaseResponse::Paginate('Berhasil mengambil list data product !', $resource->collection, $meta);
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
            if (auth()->user()->hasRole('outlet')) $data["outlet_id"] = auth()->user()->outlet_id;
            else if (auth()->user()->hasRole('warehouse')) $data["warehouse_id"] = auth()->user()->warehouse_id;
            $data = $this->productService->injectDensityToDetails($data);


            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $data["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $mapProduct = $this->productService->dataProduct($data);

            $result_product = $this->product->store($mapProduct);

            foreach ($data["product_details"] as $detail) {
                $detail["product_id"] = $result_product->id;
                $detail["category_id"] = $data["category_id"];
                $detail["unit_id"] = $data["unit_id"] ?? null;
                $detail["variant"] = $detail["variant"] ?? null;
                $detail["density"] = $detail["density"] ?? null;
                $detail["opsi"] = $detail["opsi"] ?? null;
                $mappingDetail = $this->productDetailService->dataProductDetail($detail);
                $storedDetail = $this->productDetail->store($mappingDetail);

                $this->productStock->store([
                    'warehouse_id' => auth()->user()?->hasRole('warehouse') ? auth()->user()->warehouse_id : null,
                    'outlet_id' => auth()->user()?->hasRole('outlet') ? auth()->user()->outlet_id : null,
                    'product_id' => $result_product->id,
                    'product_code' => $mappingDetail["product_code"],
                    'product_detail_id' => $storedDetail->id,
                    'stock' => $storedDetail->stock ?? 0,
                ]);
            }

            DB::commit();
            return BaseResponse::Create('Berhasil membuat product ', new ProductResource($result_product->load(['details'])));
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

        $product->loadMissing([
            'details.productStockWarehouse',
            'details.productStockOutlet',
            'details.category',
            'productBundling.details.productDetail.product',
        ]);

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

            if (auth()->user()->hasRole('outlet')) $data["outlet_id"] = auth()->user()->outlet_id;
            else if (auth()->user()->hasRole('warehouse')) $data["warehouse_id"] = auth()->user()->warehouse_id;
            $data = $this->productService->injectDensityToDetails($data);

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) $data["store_id"] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            $select_product = $this->product->show($id);

            if (!$select_product) {
                DB::rollBack();
                return BaseResponse::Notfound("Produk tidak ditemukan");
            }

            $mapProduct = $this->productService->dataProductUpdate($data, $select_product);
            $this->product->update($id, $mapProduct);
            $products = $select_product->details->where('is_delete', 0);

            foreach ($data["product_details"] as $detail) {
                $detail["product_id"] = $id;
                $detail["category_id"] = $data["category_id"];
                $detail["unit_id"] = $data["unit_id"] ?? null;

                /**
                 * Pengecekan apakah data varian yang dikirim sudah ada atau belum
                 */
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
                    $resultStore = $this->productDetail->store($mappingDetail);
                    $idDetail = $resultStore->id;
                }
                
                // insert stock into product stock warehouse
                if(auth()->user()?->hasRole('warehouse')) {
                    $warehouseStock = $this->productStock->customQuery([
                        "warehouse_id" => auth()->user()?->warehouse_id,
                        "product_detail_id" => $idDetail
                    ])->first();

                    if($warehouseStock) {
                        $warehouseStock->stock = isset($detail['stock']) ? $detail['stock'] : $warehouseStock->stock;
                        $warehouseStock->save();
                    } else if($idDetail) {
                        $this->productStock->store([
                            "warehouse_id" => auth()->user()?->warehouse_id,
                            "stock" => isset($detail['stock']) ? $detail['stock'] : 0,
                            "product_detail_id" => $idDetail
                        ]);
                    }
                }

                // insert stock into product stock outlet
                if(auth()->user()?->hasRole('outlet')) {
                    $outletStock = $this->productStock->customQuery([
                        "outlet_id" => auth()->user()?->outlet_id,
                        "product_detail_id" => $idDetail
                    ])->first();

                    if($outletStock) {
                        $outletStock->stock = isset($detail['stock']) ? $detail['stock'] : $outletStock->stock;
                        $outletStock->save();
                    } else if($idDetail) {
                        $this->productStock->store([
                            "outlet_id" => auth()->user()?->outlet_id,
                            "stock" => isset($detail['stock']) ? $detail['stock'] : 0,
                            "product_detail_id" => $idDetail
                        ]);
                    }
                }
            }

            foreach ($products as $product_detail) {
                $product_detail->is_delete = true;
                $product_detail->save();
            }

            $result_product = $this->product->checkActiveWithDetail($id);
            DB::commit();
            return BaseResponse::Ok('Berhasil update product', new ProductResource($result_product));
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
            ];

            if (auth()->user()->hasRole('warehouse')) $payload["warehouse_id"] = auth()->user()->warehouse_id;
            if (auth()->user()->hasRole('outlet')) $payload["outlet_id"] = auth()->user()->outlet_id;


            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
                $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            }

            $products = $this->product->getListProduct($payload);
            
            return BaseResponse::Ok("Berhasil mengambil data product", ProductResource::collection($products));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listProductV2(Request $request)
    {
        try {
            $payload = [
                'is_delete' => $request->get('is_delete', 0),
                'search' => $request->get('search'),
            ];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
                $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            }

            if (auth()?->user()?->outlet_id) {
                $payload['outlet_id'] = auth()?->user()?->outlet_id;
            }

            $products = $this->product->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data product", $products);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function listProductWithoutBundling(Request $request)
    {
        try {
            $payload = [
                'is_delete' => $request->get('is_delete', 0),
                'search' => $request->get('search'),
            ];

            $user = auth()->user();

            if ($user->hasRole('warehouse')) {
                $payload["warehouse_id"] = $user->warehouse_id;
            }

            if ($user->hasRole('outlet')) {
                $payload["outlet_id"] = $user->outlet_id;
            }

            if ($user->store?->id || $user->store_id) {
                $payload['store_id'] = $user->store?->id ?? $user->store_id;
            }

            $products = $this->product->getListProductWithoutBundling($payload);

            return BaseResponse::Ok("Berhasil mengambil data product non-bundling", ProductResource::collection($products));
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function downloadExample()
    {
        $filePath = public_path('ExampleProduct.xlsx');

        if (!file_exists($filePath)) {
            return BaseResponse::Notfound('File tidak ditemukan.');
        }

        return Response::download($filePath, 'ExampleProduct.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function syncListProduct(Request $request)
    {
        try {
            $payload = [
                'is_delete' => $request->get('is_delete', 0),
                'search' => $request->get('search'),
            ];

            if (auth()?->user()?->store?->id || auth()?->user()?->store_id) {
                $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;
            }

            if (auth()?->user()?->outlet_id) {
                $payload['outlet_id'] = auth()?->user()?->outlet_id;
            }

            $products = $this->product->customQuery($payload)->paginate($request->page ?? 100);

            $meta = PaginationHelper::meta($products);

            return BaseResponse::Paginate("Berhasil sync list product", $products["data"], $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function export()
    {
        try {
            $products = $this->product->customQuery(['is_delete' => 0])->get();
            $data = $this->product->mappingExcel($products);
            $export = new ProductExport($data);

            return Excel::download($export, 'products.xlsx');
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    public function exportPdf() {
        try {
            $products = $this->product->customQuery(['is_delete' => 0])->get();

            $pdf = Pdf::loadView('pdf.product', compact('products'));
            return $pdf->download('products.pdf');
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }      
    }
}
