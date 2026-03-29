<?php

namespace Modules\Order\Models;

use App\Models\User;
use App\Traits\BelongsToTenantModel;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Order\Enums\VehicleTypeEnum;
use Modules\Order\Enums\ZoneTypeEnum;
use Modules\Outlet\Models\Outlet;

class ShippingZone extends Model
{
    use HasFactory, HasUuid, SoftDeletes, BelongsToTenantModel;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_shipping_zones';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'outlet_id',
        'name',
        'description',
        'color',
        'zone_type',
        'latitude',
        'longitude',
        'radius',
        'polygon_coordinates',
        // Pricing
        'delivery_fee',
        'min_order_amount',
        'free_delivery_threshold',
        'peak_hour_surcharge',
        'price_per_km',
        // Capacity
        'max_orders_per_hour',
        'max_weight_kg',
        'max_items',
        // Vehicle
        'vehicle_type',
        'driver_requirements',
        'requires_special_handling',
        // Time
        'estimated_delivery_minutes',
        'operating_hours',
        'peak_hours',
        'blocked_dates',
        // Status
        'is_active',
        'priority',
        // schedules
        'time_start',
        'time_end',
        // Audit
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'zone_type' => ZoneTypeEnum::class,
        'vehicle_type' => VehicleTypeEnum::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'integer',
        'polygon_coordinates' => 'array',
        'delivery_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'free_delivery_threshold' => 'decimal:2',
        'peak_hour_surcharge' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'max_orders_per_hour' => 'integer',
        'max_weight_kg' => 'decimal:2',
        'max_items' => 'integer',
        'requires_special_handling' => 'boolean',
        'estimated_delivery_minutes' => 'integer',
        'operating_hours' => 'array',
        'peak_hours' => 'array',
        'blocked_dates' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Relation to outlet.
     * Note: Uses withoutGlobalScopes to bypass IsTenant scope.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->withoutGlobalScopes();
    }

    /**
     * Relation to order shippings (orders using this zone).
     */
    public function shippings(): HasMany
    {
        return $this->hasMany(OrderShipping::class);
    }

    /**
     * Get orders through shippings.
     */
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(
            Order::class,
            OrderShipping::class,
            'shipping_zone_id', // Foreign key on order_shippings table
            'id',               // Foreign key on order_orders table
            'id',               // Local key on order_shipping_zones table
            'order_id'          // Local key on order_shippings table
        );
    }

    /**
     * Relation to creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation to updater.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ========== SCOPES ==========

    /**
     * Scope to get only active zones.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Scope to filter by outlet.
     */
    public function scopeForOutlet($query, int $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }

    /**
     * Scope to filter by vehicle type.
     */
    public function scopeForVehicle($query, string $vehicleType)
    {
        return $query->where('vehicle_type', $vehicleType);
    }

    // ========== ZONE DETECTION ==========

    /**
     * Check if a given point is within this zone.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->zone_type === ZoneTypeEnum::Circle) {
            return $this->isPointInCircle($lat, $lng);
        }

        return $this->isPointInPolygon($lat, $lng);
    }

    /**
     * Check if point is within circle zone.
     */
    protected function isPointInCircle(float $lat, float $lng): bool
    {
        $distance = $this->calculateDistance(
            $this->latitude,
            $this->longitude,
            $lat,
            $lng
        );

        return $distance <= $this->radius;
    }

    /**
     * Check if point is within polygon zone.
     */
    protected function isPointInPolygon(float $lat, float $lng): bool
    {
        $polygon = $this->polygon_coordinates;

        if (!$polygon || count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $count = count($polygon);
        $j = $count - 1;

        for ($i = 0; $i < $count; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $lng) != ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Calculate distance between two points using Haversine formula.
     * Returns distance in meters.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get distance from zone center to a point in kilometers.
     */
    public function getDistanceToPoint(float $lat, float $lng): float
    {
        return $this->calculateDistance($this->latitude, $this->longitude, $lat, $lng) / 1000;
    }

    // ========== PRICING CALCULATIONS ==========

    /**
     * Calculate delivery fee for a given point.
     */
    public function calculateDeliveryFee(float $lat, float $lng, float $orderAmount = 0): float
    {
        // Free delivery if above threshold
        if ($this->free_delivery_threshold && $orderAmount >= $this->free_delivery_threshold) {
            return 0;
        }

        $fee = (float) $this->delivery_fee;

        // Add per-km pricing if enabled
        if ($this->price_per_km) {
            $distance = $this->getDistanceToPoint($lat, $lng);
            $fee += $distance * (float) $this->price_per_km;
        }

        // Add peak hour surcharge if applicable
        if ($this->isPeakHour()) {
            $fee += (float) $this->peak_hour_surcharge;
        }

        return round($fee, 2);
    }

    /**
     * Check if current time is within peak hours.
     */
    public function isPeakHour(): bool
    {
        if (!$this->peak_hours) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->peak_hours['start'] ?? null;
        $end = $this->peak_hours['end'] ?? null;

        if (!$start || !$end) {
            return false;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * Check if zone is operating now.
     */
    public function isOperatingNow(): bool
    {
        // Check blocked dates
        if ($this->blocked_dates && in_array(now()->format('Y-m-d'), $this->blocked_dates)) {
            return false;
        }

        // Check operating hours
        if (!$this->operating_hours) {
            return true; // No restrictions
        }

        $dayOfWeek = strtolower(now()->format('l'));
        $dayHours = $this->operating_hours[$dayOfWeek] ?? null;

        if (!$dayHours) {
            return false; // Closed on this day
        }

        $now = now()->format('H:i');
        $open = $dayHours['open'] ?? '00:00';
        $close = $dayHours['close'] ?? '23:59';

        return $now >= $open && $now <= $close;
    }

    // ========== STATIC METHODS ==========

    /**
     * Find all zones that contain a given point.
     */
    public static function findZonesForPoint(float $lat, float $lng, ?int $outletId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::active()->byPriority()->with('outlet');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        return $query->get()->filter(fn ($zone) => $zone->containsPoint($lat, $lng));
    }

    /**
     * Get the best zone (highest priority) for a given point.
     */
    public static function getBestZoneForPoint(float $lat, float $lng, ?int $outletId = null): ?self
    {
        return static::findZonesForPoint($lat, $lng, $outletId)->first();
    }

    /**
     * Check if delivery is available for a given point.
     */
    public static function isDeliveryAvailable(float $lat, float $lng, ?int $outletId = null): bool
    {
        $zone = static::getBestZoneForPoint($lat, $lng, $outletId);

        return $zone !== null && $zone->isOperatingNow();
    }

    /**
     * Get delivery info for a given point.
     */
    public static function getDeliveryInfo(float $lat, float $lng, ?int $outletId = null, float $orderAmount = 0): ?array
    {
        $zone = static::getBestZoneForPoint($lat, $lng, $outletId);

        if (!$zone) {
            return null;
        }

        return [
            'zone' => $zone,
            'is_available' => $zone->isOperatingNow(),
            'delivery_fee' => $zone->calculateDeliveryFee($lat, $lng, $orderAmount),
            'estimated_minutes' => $zone->estimated_delivery_minutes,
            'min_order_amount' => $zone->min_order_amount,
            'free_delivery_threshold' => $zone->free_delivery_threshold,
            'vehicle_type' => $zone->vehicle_type,
            'distance_km' => round($zone->getDistanceToPoint($lat, $lng), 2),
        ];
    }
}
