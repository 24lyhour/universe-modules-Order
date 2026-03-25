<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentStatusEnum;
use Modules\Outlet\Models\Outlet;

class Order extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_orders';

    /**
     * Get the route key for the model (use UUID instead of ID).
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
        'order_number',
        'customer_id',
        'outlet_id',
        'cart_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'notes',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'shipped_at'      => 'datetime',
        'delivered_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
        'completed_at'    => 'datetime',
        'status'          => OrderStatusEnum::class,
        'payment_status'  => PaymentStatusEnum::class,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the outlet associated with the order.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Get the cart associated with the order.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the shipping information for the order.
     */
    public function shipping(): HasOne
    {
        return $this->hasOne(OrderShipping::class);
    }

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === OrderStatusEnum::Pending;
    }

    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === OrderStatusEnum::Cancelled;
    }

    /**
     * Check if the order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === OrderStatusEnum::Delivered;
    }

    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatusEnum::Paid;
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Check if order can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status->canBeRefunded();
    }

    /**
     * Get the total quantity of items in the order.
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Calculate totals from items.
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_amount');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
    }
}
