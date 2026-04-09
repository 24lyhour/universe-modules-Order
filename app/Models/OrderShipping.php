<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_shippings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'shipping_zone_id',
        'delivery_id',
        'carrier',
        'method',
        'delivery_price',
        'delivery_paid',
        'shipping_cost',
        'tracking_number',
        'recipient_name',
        'phone',
        'street_1',
        'street_2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'distance_km',
        'weight',
        'notes',
        'estimated_delivery_at',
        'shipped_at',
        'delivered_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'shipping_cost'         => 'decimal:2',
        'weight'                => 'decimal:2',
        'latitude'              => 'decimal:8',
        'longitude'             => 'decimal:8',
        'delivery_paid'         => 'boolean',
        'distance_km'           => 'decimal:2',
        'estimated_delivery_at' => 'datetime',
        'shipped_at'            => 'datetime',
        'delivered_at'          => 'datetime',
    ];

    /**
     * Get the order that owns the shipping.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the shipping zone for this shipping.
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street_1,
            $this->street_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if the shipping has tracking.
     */
    public function hasTracking(): bool
    {
        return !empty($this->tracking_number);
    }

    /**
     * Check if the shipping is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Check if the shipping is shipped.
     */
    public function isShipped(): bool
    {
        return $this->shipped_at !== null;
    }

    /**
     * Check if the shipping has GPS coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get the GPS coordinates as an array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
