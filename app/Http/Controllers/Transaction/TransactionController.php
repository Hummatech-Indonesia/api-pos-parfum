<?php

namespace App\Http\Controllers\Transaction;

use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Transaction\TransactionDetailInterface;
use App\Contracts\Interfaces\Transaction\TransactionInterface;
use App\Contracts\Interfaces\Transaction\VoucherUsedInterface;
use App\Helpers\BaseResponse;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionRequest;
use App\Http\Requests\Transaction\TransactionSyncRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionDetailResource;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    private TransactionInterface $transaction;
    private TransactionDetailInterface $transactionDetail;
    private VoucherUsedInterface $voucherUsed;
    private DiscountVoucherInterface $discountVoucher;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;
    private TransactionService $transactionService;

    public function __construct(
        TransactionInterface $transaction,
        TransactionDetailInterface $transactionDetail,
        VoucherUsedInterface $voucherUsed,
        DiscountVoucherInterface $discountVoucher,
        ProductDetailInterface $productDetail,
        ProductStockInterface $productStock,
        TransactionService $transactionService
    ) {
        $this->transaction = $transaction;
        $this->transactionDetail = $transactionDetail;
        $this->voucherUsed = $voucherUsed;
        $this->discountVoucher = $discountVoucher;
        $this->productDetail = $productDetail;
        $this->productStock = $productStock;
        $this->transactionService = $transactionService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $payload = [];

            if ($request->search) $payload["search"] = $request->search;
            if ($request->start_date) $payload["start_date"] = $request->start_date;
            if ($request->end_date) $payload["end_date"] = $request->end_date;
            if ($request->min_quantity) $payload["min_quantity"] = $request->min_quantity;
            if ($request->max_quantity) $payload["max_quantity"] = $request->max_quantity;
            if ($request->min_price) $payload["min_price"] = $request->min_price;
            if ($request->max_price) $payload["max_price"] = $request->max_price;

            if ($storeId = auth()?->user()?->store?->id ?? auth()?->user()?->store_id) {
                $payload['store_id'] = $storeId;
            }
            if ($outletId = auth()?->user()?->outlet?->id ?? auth()?->user()?->outlet_id) {
                $payload['outlet_id'] = $outletId;
            }
            if ($warehouseId = auth()?->user()?->warehouse?->id ?? auth()?->user()?->warehouse_id) {
                $payload['warehouse_id'] = $warehouseId;
            }

            $perPage = (int) ($request->per_page ?? 10);
            $page = (int) ($request->page ?? 1);

            $transaction = $this->transaction->customPaginate($perPage, $page, $payload);

            $resource = TransactionResource::collection($transaction);
            $result = $resource->collection->values();
            $meta = PaginationHelper::meta($transaction);

            return BaseResponse::Paginate('Berhasil mengambil list data transaksi!', $result, $meta);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {

            if (auth()->user()?->outlet_id) $data['outlet_id'] = auth()->user()->outlet_id;
            else if (auth()->user()?->warehouse_id) $data['warehouse_id'] = auth()->user()->warehouse_id;
            $transaction = $this->transaction->store($this->transactionService->store($data));

            // use discount
            foreach ($data["discounts"] as $item => $value) {
                $discount = $this->discountVoucher->show($value);

                if (!$discount) return BaseResponse::Error("Discount voucher yang dipilih sudah tidak valid, silahkan pilih yang lain!", null);

                if ($discount->used > $discount->max_used) return BaseResponse::Error("Discount voucher sudah habis, silahkan pilih yang lain!", null);

                if ($discount->expired > now()) return BaseResponse::Error("Discount voucher telah habis masa berlakunya, silahkan pilih yang lain!", null);

                $discount->used += 1;
                $discount->save();

                $this->voucherUsed->store([
                    "store_id" => auth()->user()?->store_id ?? auth()->user()?->store?->id,
                    "discount_voucher_id" => $value,
                    "description" => "Discount " . $discount->name . " telah digunakan dalam transaksi pada " . date("d-m-Y")
                ]);
            }

            // handling product
            foreach ($data["transaction_detail"] as $item) {

                $productStock = $this->productStock->customQuery(["product_detail_id" => $item['product_detail_id'], 'outlet_id' => auth()->user()?->outlet_id])->first();

                if (!$productStock) return BaseResponse::Error("Product tidak memiliki stock yang terdaftar di dalam outlet, silahkan check kembali dalam gudang!", null);

                if ($productStock->stock < $item["quantity"]) return BaseResponse::Error("Product tidak memiliki stock memadai!", null);

                $productDetail = $this->productDetail->show($item['product_detail_id']);

                if (!$productDetail) return BaseResponse::Error("Product tidak terdaftar, silahkan check ke admin!", null);

                $used_quantity = $item["quantity"];
                if (strtolower($item["unit"]) == "gram") $used_quantity = $item["quantity"] * $productDetail->density;

                $productStock->stock -= $used_quantity;
                $productStock->save();


                $this->transactionDetail->store([
                    "transaction_id" => $transaction->id,
                    "product_detail_id" => $item['product_detail_id'],
                    "quantity" => $item['quantity'],
                    "price" => $item['price'],
                    "unit" => $item['unit'],
                ]);
            }
            DB::commit();
            return BaseResponse::Ok("Berhasil melakukan transaksi", null);
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
        try {
            $transaction = $this->transaction->show($id);

            if (!$transaction) {
                return BaseResponse::NotFound("Transaksi tidak ditemukan", null);
            }

            $details = $this->transactionDetail->customQuery([
                'transaction_id' => $id
            ])->with(['productDetail.product', 'productDetail.unitRelasi'])->get();

            $product = $details;

            $vouchers = $this->voucherUsed->customQuery([
                'store_id' => $transaction->store_id,
            ])->where('description', 'like', "%{$transaction->created_at->format('d-m-Y')}%")->get();

            // Total harga barang sebelum diskon
            $totalHargaBarang = $details->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            // Total diskon dari voucher
            $totalDiskon = $vouchers->sum('discount_value'); // pastikan ada kolom ini

            // Pajak dari transaksi
            $pajak = $transaction->amount_tax;
            Log::info("Data transaction detail", [$product]);
            return BaseResponse::Ok("Berhasil mengambil detail transaksi", [
                'transaction_code' => $transaction->transaction_code,
                'created_at' => $transaction->created_at->format('d F Y, H:i'),
                'kasir_name' => $transaction->cashier?->name ?? 'Kasir',
                'buyer_name' => $transaction->user?->name ?? $transaction->user_name,
                'is_member' => $transaction->user_id ? true : false,
                'payment_method' => $transaction->payment_method,
                'total_price' => $transaction->total_price,
                'total_tax' => $pajak,
                'total_discount' => $totalDiskon,
                'total_barang' => $totalHargaBarang,
                'outlet' => $transaction->outlet,
                'warehouse' => $transaction->warehouse,
                'details' => TransactionDetailResource::collection($product),
            ]);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
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

    public function getData(Request $request)
    {
        try {
            $payload = [];

            if ($storeId = auth()?->user()?->store?->id ?? auth()?->user()?->store_id) {
                $payload['store_id'] = $storeId;
            }

            $transactions = $this->transaction->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data transaction", $transactions);
        } catch (\Throwable $th) {
            return BaseResponse::Error($th->getMessage(), null);
        }
    }


    /**
     * Store a newly created resource in storage with sync mobile.
     */
    public function syncStoreData(TransactionSyncRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {

            foreach ($data['transaction'] as $trans) {
                if (auth()->user()?->outlet_id) $trans['outlet_id'] = auth()->user()->outlet_id;
                else if (auth()->user()?->warehouse_id) $trans['warehouse_id'] = auth()->user()->warehouse_id;
                $transaction = $this->transaction->store($this->transactionService->store($trans));

                // use discount
                foreach ($trans["discounts"] as $item => $value) {
                    $discount = $this->discountVoucher->show($value);

                    if (!$discount) return BaseResponse::Error("Discount voucher yang dipilih sudah tidak valid, silahkan pilih yang lain!", null);

                    if ($discount->used > $discount->max_used) return BaseResponse::Error("Discount voucher sudah habis, silahkan pilih yang lain!", null);

                    if ($discount->expired > now()) return BaseResponse::Error("Discount voucher telah habis masa berlakunya, silahkan pilih yang lain!", null);

                    $discount->used += 1;
                    $discount->save();

                    $this->voucherUsed->store([
                        "store_id" => auth()->user()?->store_id ?? auth()->user()?->store?->id,
                        "discount_voucher_id" => $value,
                        "description" => "Discount " . $discount->name . " telah digunakan dalam transaksi pada " . date("d-m-Y")
                    ]);
                }

                // handling product
                foreach ($trans["transaction_detail"] as $item) {

                    $productStock = $this->productStock->customQuery(["product_detail_id" => $item['product_detail_id'], 'outlet_id' => auth()->user()?->outlet_id])->first();

                    if (!$productStock) return BaseResponse::Error("Product tidak memiliki stock yang terdaftar di dalam outlet, silahkan check kembali dalam gudang!", null);

                    if ($productStock->stock < $item["quantity"]) return BaseResponse::Error("Product tidak memiliki stock memadai!", null);

                    $productDetail = $this->productDetail->show($item['product_detail_id']);

                    if (!$productDetail) return BaseResponse::Error("Product tidak terdaftar, silahkan check ke admin!", null);

                    $used_quantity = $item["quantity"];
                    if (strtolower($item["unit"]) == "gram") $used_quantity = $item["quantity"] * $productDetail->density;

                    $productStock->stock -= $used_quantity;
                    $productStock->save();


                    $this->transactionDetail->store([
                        "transaction_id" => $transaction->id,
                        "product_detail_id" => $item['product_detail_id'],
                        "quantity" => $item['quantity'],
                        "price" => $item['price'],
                        "unit" => $item['unit'],
                    ]);
                }
            }
            DB::commit();
            return BaseResponse::Ok("Berhasil melakukan sinkronisasi transaksi", null);
        } catch (\Throwable $th) {
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
