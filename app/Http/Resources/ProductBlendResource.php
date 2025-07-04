    <?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class ProductBlendResource extends JsonResource
    {
        /**
         * Transform the resource into an array.
         *
         * @return array<string, mixed>
         */
        public function toArray(Request $request): array
        {
            return [
                'id' => $this->id,
                'product_detail_id' => $this->product_detail_id,
                'unit_id' => $this->unit_id,
                'product_image' => $this->productDetail?->product_image ?? null,
                'product_name' => $this->productDetail?->product?->name ?? null,
                'variant_blending' => $this->productDetail?->variant_name ?? null,
                'quantity' => $this->result_stock,
                'description' => $this->description,
                'date' => $this->date,
                'created_at' => $this->created_at->toDateTimeString(),
                'used_product_count' => $this->used_product_count,
                'used_products' => $this->productBlendDetails->map(function ($detail) {
                    return [
                        'product_detail_id' => $detail->product_detail_id,
                        'product_name' => $detail->productDetail?->product->name ?? null,
                        'variant_name' => $detail->productDetail?->variant_name ?? null,
                        'used_stock' => $detail->used_stock,
                        'stock' => $detail->productStock?->stock ?? 0,
                    ];
                }),
            ];
        }
    }
