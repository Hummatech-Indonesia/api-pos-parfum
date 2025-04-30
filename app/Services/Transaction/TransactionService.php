<?php 

namespace App\Services\Transaction;

use App\Models\Outlet;
use App\Traits\UploadTrait;
use Error;
use Illuminate\Support\Facades\Log;

class TransactionService
{

    public function store(array $data)
    {
        try {
            $tax = isset($data["amount_tax"]) ? isset($data["amount_tax"]) : 0;
            $price = isset($data["amount_price"]) ? $data["amount_price"] : 0;
            $total_price = $tax + $price;

            return [
                'transaction_code' => date('Ymdhms'),
                'store_id' => auth()->user()?->store_id ?? auth()->user()?->store?->id,
                'transaction_status' => "Success",
                'user_id' => isset($data["user_id"]) ? $data["user_id"] : null,
                'user_name' => isset($data["user_name"]) ? $data["user_name"] : null,
                'amount_price' => isset($data["amount_price"]) ? $data["amount_price"] : null,
                'tax' => isset($data["tax"]) ? $data["tax"] : null,
                'amount_tax' => isset($data["amount_tax"]) ? $data["amount_tax"] : null,
                'total_price' => $total_price,
                'payment_method' => isset($data["payment_method"]) ? $data["payment_method"] : null,
                'note' => isset($data["note"]) ? $data["note"] : null,
                'payment_time' => now()
            ];
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            throw new Error($th->getMessage(), 400);
        }
    }

}