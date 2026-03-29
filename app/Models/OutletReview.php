<?php

namespace Modules\Order\Models;

use App\Traits\BelongsToTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Outlet\Models\Outlet;

class OutletReview extends Model
{
    use HasFactory, BelongsToTenantModel;

    protected $table = 'order_outlet_reviews';

    protected $fillable = [
        'uuid',
        'customer_id',
        'order_id',
        'outlet_id',
        'overall_rating',
        'food_rating',
        'service_rating',
        'delivery_rating',
        'packaging_rating',
        'comment',
        'images',
        'tags',
        'reply',
        'replied_at',
        'is_active',
        'is_verified',
        'helpful_count',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'replied_at' => 'datetime',
        'overall_rating' => 'integer',
        'food_rating' => 'integer',
        'service_rating' => 'integer',
        'delivery_rating' => 'integer',
        'packaging_rating' => 'integer',
        'helpful_count' => 'integer',
    ];

    // Available tags for quick selection
    public const TAGS = [
        'fast_delivery' => 'Fast Delivery',
        'friendly_staff' => 'Friendly Staff',
        'clean_packaging' => 'Clean Packaging',
        'great_taste' => 'Great Taste',
        'good_portions' => 'Good Portions',
        'value_for_money' => 'Value for Money',
        'fresh_food' => 'Fresh Food',
        'accurate_order' => 'Accurate Order',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OutletReview $review) {
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

    /**
     * Note: Uses withoutGlobalScopes to bypass IsTenant scope.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->withoutGlobalScopes();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeWithMinRating($query, int $rating)
    {
        return $query->where('overall_rating', '>=', $rating);
    }

    public function scopeForOutlet($query, int $outletId)
    {
        return $query->where('outlet_id', $outletId);
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

    /**
     * Calculate average rating from all categories.
     */
    public function getAverageRatingAttribute(): float
    {
        $ratings = array_filter([
            $this->food_rating,
            $this->service_rating,
            $this->delivery_rating,
            $this->packaging_rating,
        ]);

        if (empty($ratings)) {
            return (float) $this->overall_rating;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }
}
