<?php

namespace Modules\Order\Enums;

enum PaymentStatusEnum: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Partial = 'partial';

    /**
     * Get the label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::Partial => 'Partial',
        };
    }

    /**
     * Get the badge variant for the payment status.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'outline',
            self::Paid => 'success',
            self::Failed => 'destructive',
            self::Refunded => 'secondary',
            self::Partial => 'default',
        };
    }

    /**
     * Get all payment statuses as options for select inputs.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }

    /**
     * Get all payment status values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Check if payment can be retried.
     */
    public function canRetry(): bool
    {
        return in_array($this, [self::Pending, self::Failed]);
    }
}
