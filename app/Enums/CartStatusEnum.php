<?php

namespace Modules\Order\Enums;

enum CartStatusEnum: string
{
    case Active = 'active';
    case Abandoned = 'abandoned';
    case Converted = 'converted';
    case Expired = 'expired';

    /**
     * Get the label for the cart status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Abandoned => 'Abandoned',
            self::Converted => 'Converted',
            self::Expired => 'Expired',
        };
    }

    /**
     * Get the badge variant for the cart status.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Active => 'default',
            self::Abandoned => 'outline',
            self::Converted => 'secondary',
            self::Expired => 'destructive',
        };
    }

    /**
     * Get all cart statuses as options for select inputs.
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
     * Get all cart status values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if cart can be converted to order.
     */
    public function canConvert(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if the cart is still modifiable.
     */
    public function isModifiable(): bool
    {
        return $this === self::Active;
    }
}
