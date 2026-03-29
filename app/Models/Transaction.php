<?php

namespace Modules\Order\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\PaymentMethodEnum;
use Modules\Order\Enums\TransactionStatusEnum;

class Transaction extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_transactions';

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
        'transaction_number',
        'order_id',
        'refund_id',
        'customer_id',
        'type',
        'payment_method',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'status',
        'gateway_transaction_id',
        'gateway_response',
        'notes',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'status' => TransactionStatusEnum::class,
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Transaction types.
     */
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Transaction $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = static::generateTransactionNumber();
            }
            if (empty($transaction->currency)) {
                $transaction->currency = 'USD';
            }
        });
    }

    /**
     * Generate a unique transaction number.
     */
    public static function generateTransactionNumber(): string
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Relation to the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation to the refund.
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * Relation to the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if transaction is payment type.
     */
    public function isPayment(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    /**
     * Check if transaction is refund type.
     */
    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === TransactionStatusEnum::Completed;
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === TransactionStatusEnum::Pending;
    }

    /**
     * Mark transaction as completed.
     */
    public function markAsCompleted(string $gatewayTransactionId = null): void
    {
        $this->update([
            'status' => TransactionStatusEnum::Completed,
            'gateway_transaction_id' => $gatewayTransactionId ?? $this->gateway_transaction_id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed(string $reason, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => TransactionStatusEnum::Failed,
            'failure_reason' => $reason,
            'gateway_response' => $gatewayResponse,
            'failed_at' => now(),
        ]);
    }

    /**
     * Scope for payment transactions.
     */
    public function scopePayments($query)
    {
        return $query->where('type', self::TYPE_PAYMENT);
    }

    /**
     * Scope for refund transactions.
     */
    public function scopeRefunds($query)
    {
        return $query->where('type', self::TYPE_REFUND);
    }

    /**
     * Scope for successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', TransactionStatusEnum::Completed);
    }
}
