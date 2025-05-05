<?php

namespace App\Http\Controllers\Transaction;

use App\Contracts\Interfaces\Master\DiscountVoucherInterface;
use App\Contracts\Interfaces\Master\ProductDetailInterface;
use App\Contracts\Interfaces\Master\ProductStockInterface;
use App\Contracts\Interfaces\Transaction\TransactionDetailInterface;
use App\Contracts\Interfaces\Transaction\TransactionInterface;
use App\Contracts\Interfaces\Transaction\VoucherUsedInterface;
use App\Helpers\BaseResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\TransactionRequest;
use App\Http\Requests\Transaction\TransactionSyncRequest;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private TransactionInterface $transaction;
    private TransactionDetailInterface $transactionDetail;
    private VoucherUsedInterface $voucherUsed;
    private DiscountVoucherInterface $discountVoucher;
    private ProductDetailInterface $productDetail;
    private ProductStockInterface $productStock;
    private TransactionService $transactionService;

    public function __construct(TransactionInterface $transaction, TransactionDetailInterface $transactionDetail, 
    VoucherUsedInterface $voucherUsed, DiscountVoucherInterface $discountVoucher, ProductDetailInterface $productDetail,
    ProductStockInterface $productStock, TransactionService $transactionService
    )
    {
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
        try{
            $payload = [];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  

            $transaction = $this->transaction->customPaginate($request->per_page, $request->page, $payload)->toArray();

            $result = $transaction["data"];
            unset($transaction["data"]);
    
            return BaseResponse::Paginate('Berhasil mengambil list data shift!', $result, $transaction);
        } catch(\Throwable $th) {
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

            $transaction = $this->transaction->store($this->transactionService->store($data));

            // use discount
            foreach($data["discounts"] as $item => $value) {
                $discount = $this->discountVoucher->show($value);

                if(!$discount) return BaseResponse::Error("Discount voucher yang dipilih sudah tidak valid, silahkan pilih yang lain!", null);
                
                if($discount->used > $discount->max_used) return BaseResponse::Error("Discount voucher sudah habis, silahkan pilih yang lain!", null);
                
                if($discount->expired > now()) return BaseResponse::Error("Discount voucher telah habis masa berlakunya, silahkan pilih yang lain!", null);

                $discount->used += 1;
                $discount->save();

                $this->voucherUsed->store([
                    "store_id" => auth()->user()?->store_id ?? auth()->user()?->store?->id,
                    "discount_voucher_id" => $value,
                    "description" => "Discount ". $discount->name . " telah digunakan dalam transaksi pada ". date("d-m-Y")
                ]);
            }

            // handling product
            foreach($data["transaction_detail"] as $item) {

                $productStock = $this->productStock->customQuery(["product_detail_id" => $item['product_detail_id'], 'outlet_id' => auth()->user()?->outlet_id])->first();
                
                if(!$productStock) return BaseResponse::Error("Product tidak memiliki stock yang terdaftar di dalam outlet, silahkan check kembali dalam gudang!", null);
                
                if($productStock->stock < $item["quantity"]) return BaseResponse::Error("Product tidak memiliki stock memadai!", null);
                
                $productDetail = $this->productDetail->show($item['product_detail_id']);

                if(!$productDetail) return BaseResponse::Error("Product tidak terdaftar, silahkan check ke admin!", null);
                
                $used_quantity = $item["quantity"];
                if(strtolower($item["unit"]) == "gram") $used_quantity = $item["quantity"] * $productDetail->density;

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
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        try{
            $payload = [];

            if(auth()?->user()?->store?->id || auth()?->user()?->store_id) $payload['store_id'] = auth()?->user()?->store?->id ?? auth()?->user()?->store_id;  

            $transaction = $this->transaction->customQuery($payload)->get();

            return BaseResponse::Ok("Berhasil mengambil data transaction", $transaction);
        } catch(\Throwable $th) {
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

            foreach($data['transaction'] as $trans) {
                $transaction = $this->transaction->store($this->transactionService->store($trans));
    
                // use discount
                foreach($trans["discounts"] as $item => $value) {
                    $discount = $this->discountVoucher->show($value);
    
                    if(!$discount) return BaseResponse::Error("Discount voucher yang dipilih sudah tidak valid, silahkan pilih yang lain!", null);
                    
                    if($discount->used > $discount->max_used) return BaseResponse::Error("Discount voucher sudah habis, silahkan pilih yang lain!", null);
                    
                    if($discount->expired > now()) return BaseResponse::Error("Discount voucher telah habis masa berlakunya, silahkan pilih yang lain!", null);
    
                    $discount->used += 1;
                    $discount->save();
    
                    $this->voucherUsed->store([
                        "store_id" => auth()->user()?->store_id ?? auth()->user()?->store?->id,
                        "discount_voucher_id" => $value,
                        "description" => "Discount ". $discount->name . " telah digunakan dalam transaksi pada ". date("d-m-Y")
                    ]);
                }
    
                // handling product
                foreach($trans["transaction_detail"] as $item) {
    
                    $productStock = $this->productStock->customQuery(["product_detail_id" => $item['product_detail_id'], 'outlet_id' => auth()->user()?->outlet_id])->first();
                    
                    if(!$productStock) return BaseResponse::Error("Product tidak memiliki stock yang terdaftar di dalam outlet, silahkan check kembali dalam gudang!", null);
                    
                    if($productStock->stock < $item["quantity"]) return BaseResponse::Error("Product tidak memiliki stock memadai!", null);
                    
                    $productDetail = $this->productDetail->show($item['product_detail_id']);
    
                    if(!$productDetail) return BaseResponse::Error("Product tidak terdaftar, silahkan check ke admin!", null);
                    
                    $used_quantity = $item["quantity"];
                    if(strtolower($item["unit"]) == "gram") $used_quantity = $item["quantity"] * $productDetail->density;
    
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
        }catch(\Throwable $th){
            DB::rollBack();
            return BaseResponse::Error($th->getMessage(), null);
        }
    }
}
