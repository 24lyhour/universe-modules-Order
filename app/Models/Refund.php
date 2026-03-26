<?php

namespace Modules\Order\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\RefundStatusEnum;
use Modules\Outlet\Models\Outlet;

class Refund extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_refunds';

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
        'refund_number',
        'order_id',
        'customer_id',
        'outlet_id',
        'amount',
        'reason',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'processed_at',
        'completed_at',
        'rejected_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'status' => RefundStatusEnum::class,
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Refund $refund) {
            if (empty($refund->uuid)) {
                $refund->uuid = (string) Str::uuid();
            }
            if (empty($refund->refund_number)) {
                $refund->refund_number = static::generateRefundNumber();
            }
        });
    }

    /**
     * Generate a unique refund number.
     */
    public static function generateRefundNumber(): string
    {
        $prefix = 'REF';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Relation to the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation to the outlet.
     * Note: Uses withoutGlobalScopes to bypass IsTenant scope.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->withoutGlobalScopes();
    }

    /**
     * Relation to the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation to approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Check if refund is pending.
     */
    public function isPending(): bool
    {
        return $this->status === RefundStatusEnum::Pending;
    }

    /**
     * Check if refund can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === RefundStatusEnum::Pending;
    }

    /**
     * Check if refund can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === RefundStatusEnum::Approved;
    }

    /**
     * Approve the refund.
     */
    public function approve(int $userId): void
    {
        $this->update([
            'status' => RefundStatusEnum::Approved,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the refund.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => RefundStatusEnum::Rejected,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => RefundStatusEnum::Processing,
            'processed_at' => now(),
        ]);
    }

    /**
     * Complete the refund.
     */
    public function complete(): void
    {
        $this->update([
            'status' => RefundStatusEnum::Completed,
            'completed_at' => now(),
        ]);
    }
}
