<?php

namespace Modules\Order\Enums;

enum RefundStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get the Khmer label.
     */
    public function labelKh(): string
    {
        return match ($this) {
            self::Pending => 'កំពុងរង់ចាំ',
            self::Approved => 'បានអនុម័ត',
            self::Processing => 'កំពុងដំណើរការ',
            self::Completed => 'បានបញ្ចប់',
            self::Rejected => 'បានបដិសេធ',
            self::Cancelled => 'បានលុបចោល',
        };
    }

    /**
     * Get the variant for UI styling.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Processing => 'default',
            self::Completed => 'success',
            self::Rejected => 'destructive',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * Get the icon for the status.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'Clock',
            self::Approved => 'CheckCircle',
            self::Processing => 'RefreshCw',
            self::Completed => 'CheckCircle2',
            self::Rejected => 'XCircle',
            self::Cancelled => 'Ban',
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
     * Check if refund can be processed.
     */
    public function canProcess(): bool
    {
        return in_array($this, [self::Pending, self::Approved]);
    }

    /**
     * Check if refund is final.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled]);
    }
}
