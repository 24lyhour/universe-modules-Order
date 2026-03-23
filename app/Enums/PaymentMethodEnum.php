<?php

namespace Modules\Order\Enums;

enum PaymentMethodEnum: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case DigitalWallet = 'digital_wallet';
    case PayPal = 'paypal';
    case Stripe = 'stripe';
    case COD = 'cod';

    /**
     * Get the label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Credit/Debit Card',
            self::BankTransfer => 'Bank Transfer',
            self::DigitalWallet => 'Digital Wallet',
            self::PayPal => 'PayPal',
            self::Stripe => 'Stripe',
            self::COD => 'Cash on Delivery',
        };
    }

    /**
     * Get the icon for the payment method.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'Banknote',
            self::Card => 'CreditCard',
            self::BankTransfer => 'Building',
            self::DigitalWallet => 'Wallet',
            self::PayPal => 'Wallet',
            self::Stripe => 'CreditCard',
            self::COD => 'Truck',
        };
    }

    /**
     * Get all payment methods as options for select inputs.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $method) => [
                'value' => $method->value,
                'label' => $method->label(),
            ],
            self::cases()
        );
    }

    /**
     * Get all payment method values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if payment method requires online processing.
     */
    public function isOnline(): bool
    {
        return in_array($this, [self::Card, self::PayPal, self::Stripe, self::DigitalWallet]);
    }

    /**
     * Check if payment method is offline.
     */
    public function isOffline(): bool
    {
        return in_array($this, [self::Cash, self::BankTransfer, self::COD]);
    }
}
