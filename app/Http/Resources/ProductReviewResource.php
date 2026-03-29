<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'product_id' => $this->product_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'images' => $this->images ?? [],
            'reply' => $this->reply,
            'replied_at' => $this->replied_at?->toISOString(),
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'helpful_count' => $this->helpful_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'avatar' => $this->customer->avatar ?? null,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'image' => $this->product->image ?? null,
            ]),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]),

            // Computed
            'has_reply' => $this->hasReply(),
            'formatted_date' => $this->created_at?->diffForHumans(),
        ];
    }
}
