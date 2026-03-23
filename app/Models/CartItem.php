<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_cart_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_amount',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity'     => 'integer',
        'unit_price'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (CartItem $item) {
            $item->total_amount = $item->unit_price * $item->quantity;
        });
    }

    /**
     * Get the cart that owns the item.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product associated with the cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
