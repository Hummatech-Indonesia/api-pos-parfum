<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RestockWarehouseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->groupBy(fn($item) => optional($item->created_at)?->format('Y-m-d H:i:s'))
            ->map(function ($itemsByTime, $createdTime) {

                $firstItem = $itemsByTime->first() ?? null;

                $data = [
                    "store" => [],
                    'created_at' => $createdTime,
                    'products' => $itemsByTime
                        ->groupBy(fn($item) => $item->productDetail?->product?->name ?? 'unknown')
                        ->map(function ($itemsByProduct, $productName) {
                            $firstProduct = $itemsByProduct->first()?->productDetail?->product;

                            $variants = $itemsByProduct->map(function ($item) {
                                return [
                                    'variant_id' => $item->productDetail?->id,
                                    'variant_name' => $item->productDetail?->variant_name,
                                    'requested_stock' => $item->stock,
                                    'unit_id' => $item->unit_id,
                                    'unit_name' => $item->unit?->name,
                                    'unit_code' => $item->unit?->code,
                                ];
                            })->values();

                            return [
                                'product_name' => $productName,
                                'variant_count' => $variants->count(),
                                'image' => $firstProduct?->image ?? null,
                                'category_name' => $firstProduct?->category?->name ?? null,
                                'variants' => $variants,
                            ];
                        })->values(),
                ];

                if($firstItem->store_name) {
                    $data["store"] = [
                        'store_name' => $firstItem->store_name,
                        'total_price' => $firstItem->total_price,
                        'store_location' => $firstItem->store_location,
                    ];
                }
                
                return $data;
            })->values()->toArray();
    }
}
