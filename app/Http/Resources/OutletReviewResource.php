<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Order\Models\OutletReview;

class OutletReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'outlet_id' => $this->outlet_id,

            // Ratings
            'overall_rating' => $this->overall_rating,
            'food_rating' => $this->food_rating,
            'service_rating' => $this->service_rating,
            'delivery_rating' => $this->delivery_rating,
            'packaging_rating' => $this->packaging_rating,

            // Content
            'comment' => $this->comment,
            'images' => $this->images ?? [],
            'tags' => $this->tags ?? [],

            // Reply
            'reply' => $this->reply,
            'replied_at' => $this->replied_at?->toISOString(),

            // Status
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
            'outlet' => $this->whenLoaded('outlet', fn () => [
                'id' => $this->outlet->id,
                'name' => $this->outlet->name,
            ]),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]),

            // Computed
            'has_reply' => $this->hasReply(),
            'average_rating' => $this->average_rating,
            'formatted_date' => $this->created_at?->diffForHumans(),
            'tag_labels' => $this->getTagLabels(),
        ];
    }

    protected function getTagLabels(): array
    {
        $tags = $this->tags ?? [];
        $labels = [];

        foreach ($tags as $tag) {
            if (isset(OutletReview::TAGS[$tag])) {
                $labels[] = OutletReview::TAGS[$tag];
            }
        }

        return $labels;
    }
}
