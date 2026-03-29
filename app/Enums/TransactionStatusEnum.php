<?php

namespace Modules\Order\Enums;

enum TransactionStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Get the variant for UI styling.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'default',
            self::Completed => 'success',
            self::Failed => 'destructive',
            self::Cancelled => 'secondary',
            self::Refunded => 'info',
        };
    }

    /**
     * Get all statuses as options for select.
     */
    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * Get all values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Check if transaction is final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled, self::Refunded]);
    }
}
