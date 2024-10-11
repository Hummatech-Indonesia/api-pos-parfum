<?php 

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class BaseResponse 
{
    public static function Ok(string $message, mixed $data): JsonResponse
    {
        return response()->json([
            "success" => true,
            "message" => $message,
            "data" => $data
        ])->setStatusCode(200);
    }

    public static function Error(string $message, mixed $data): JsonResponse
    {
        return response()->json([
            "success" => false,
            "message" => $message,
            "data" => $data
        ])->setStatusCode(400);
    }

    public static function ServerError(string $message): JsonResponse
    {
        return response()->json([
            "success" => false,
            "message" => $message,
            "data" => null
        ])->setStatusCode(500);
    }

    public static function Notfound(string $message): JsonResponse
    {
        return response()->json([
            "success" => false,
            "message" => $message,
            "data" => null
        ])->setStatusCode(404);
    }

    public static function Custom(bool $status, string $message, mixed $data, int $code): JsonResponse
    {
        return response()->json([
            "success" => $status,
            "message" => $message,
            "data" => $data
        ])->setStatusCode($code);
    }
}