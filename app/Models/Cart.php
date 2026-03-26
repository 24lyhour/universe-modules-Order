<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Database\Factories\CartFactory;
use Modules\Order\Enums\CartStatusEnum;
use Modules\Outlet\Models\Outlet;

class Cart extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_carts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'outlet_id',
        'status',
        'notes',
        'expires_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
        'status'     => CartStatusEnum::class,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Cart $cart) {
            if (empty($cart->uuid)) {
                $cart->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }

    /**
     * Get the customer that owns the cart.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the outlet associated with the cart.
     * Note: Uses withoutGlobalScopes to bypass IsTenant scope.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->withoutGlobalScopes();
    }

    /**
     * Get the cart items for the cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate the total amount of all items in the cart.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->items->sum('total_amount');
    }

    /**
     * Get the total quantity of items in the cart.
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get the order created from this cart.
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /**
     * Check if cart has been converted to an order.
     */
    public function isConverted(): bool
    {
        return $this->status === CartStatusEnum::Converted;
    }

    /**
     * Check if cart can be converted to order.
     */
    public function canConvert(): bool
    {
        return $this->status->canConvert();
    }

    /**
     * Check if the cart is still modifiable.
     */
    public function isModifiable(): bool
    {
        return $this->status->isModifiable();
    }

    /**
     * Convert cart to order.
     */
    public function markAsConverted(): void
    {
        $this->update(['status' => CartStatusEnum::Converted]);
    }

    /**
     * Mark cart as abandoned.
     */
    public function markAsAbandoned(): void
    {
        $this->update(['status' => CartStatusEnum::Abandoned]);
    }

    /**
     * Mark cart as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => CartStatusEnum::Expired]);
    }
}
