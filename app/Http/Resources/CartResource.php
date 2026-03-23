<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_id' => $this->customer_id,
            'outlet_id' => $this->outlet_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'outlet' => $this->whenLoaded('outlet', fn () => [
                'id' => $this->outlet->id,
                'name' => $this->outlet->name,
            ]),
            'items' => CartItemResource::collection($this->whenLoaded('items')),

            // Computed
            'total_amount' => $this->when(
                $this->relationLoaded('items'),
                fn () => (float) $this->items->sum('total_amount')
            ),
            'total_quantity' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->sum('quantity')
            ),
            'items_count' => $this->whenCounted('items'),
        ];
    }
}
