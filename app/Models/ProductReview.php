<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Product\Models\Product;

class ProductReview extends Model
{
    use HasFactory;

    protected $table = 'order_product_reviews';

    protected $fillable = [
        'uuid',
        'customer_id',
        'order_id',
        'order_item_id',
        'product_id',
        'rating',
        'comment',
        'images',
        'reply',
        'replied_at',
        'is_active',
        'is_verified',
        'helpful_count',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'replied_at' => 'datetime',
        'rating' => 'integer',
        'helpful_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductReview $review) {
            if (empty($review->uuid)) {
                $review->uuid = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function hasReply(): bool
    {
        return !empty($this->reply);
    }

    public function addReply(string $reply): void
    {
        $this->update([
            'reply' => $reply,
            'replied_at' => now(),
        ]);
    }

    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }
}
