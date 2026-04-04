<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'outlet_id' => $this->outlet_id,
            'cart_id' => $this->cart_id,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status?->value ?? $this->status,
            'payment_status' => $this->payment_status?->value ?? $this->payment_status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
            ]),
            'outlet' => $this->whenLoaded('outlet', fn () => [
                'id' => $this->outlet->id,
                'name' => $this->outlet->name,
                'address' => $this->outlet->address,
                'phone' => $this->outlet->phone,
                'logo' => $this->outlet->logo,
                'google_map_url' => $this->outlet->google_map_url,
                'latitude' => $this->outlet->latitude ? (float) $this->outlet->latitude : null,
                'longitude' => $this->outlet->longitude ? (float) $this->outlet->longitude : null,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'total_amount' => (float) $item->total_amount,
                'notes' => $item->notes,
                'product' => $item->relationLoaded('product') && $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'images' => $item->product->images,
                ] : null,
            ])->toArray()),
            'shipping' => $this->whenLoaded('shipping', fn () => [
                'id' => $this->shipping->id,
                'carrier' => $this->shipping->carrier,
                'method' => $this->shipping->method,
                'shipping_cost' => (float) $this->shipping->shipping_cost,
                'tracking_number' => $this->shipping->tracking_number,
                'recipient_name' => $this->shipping->recipient_name,
                'phone' => $this->shipping->phone,
                'street_1' => $this->shipping->street_1,
                'street_2' => $this->shipping->street_2,
                'city' => $this->shipping->city,
                'state' => $this->shipping->state,
                'postal_code' => $this->shipping->postal_code,
                'country' => $this->shipping->country,
                'latitude' => $this->shipping->latitude ? (float) $this->shipping->latitude : null,
                'longitude' => $this->shipping->longitude ? (float) $this->shipping->longitude : null,
                'estimated_delivery_at' => $this->shipping->estimated_delivery_at?->toISOString(),
                'shipped_at' => $this->shipping->shipped_at?->toISOString(),
                'delivered_at' => $this->shipping->delivered_at?->toISOString(),
            ]),

            // Computed
            'total_quantity' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->sum('quantity')
            ),
            'items_count' => $this->whenCounted('items'),
        ];
    }
}
