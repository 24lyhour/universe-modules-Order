<?php

namespace Modules\Order\Enums;

/**
 * E-commerce Order Status Workflow (Cambodia Context)
 *
 * Covers: Food Delivery, Retail E-commerce, POS
 *
 * Standard Flow:
 * 1. PENDING → Customer placed order (awaiting merchant)
 * 2. CONFIRMED → Merchant accepted order
 * 3. PREPARING → Kitchen/warehouse preparing
 * 4. READY → Ready for pickup (delivery/customer)
 * 5. DELIVERING → Rider on the way
 * 6. DELIVERED → Customer received
 * 7. COMPLETED → Finished, payment confirmed
 *
 * Alternative:
 * - CANCELLED → Order cancelled
 * - REFUNDED → Money returned
 *
 * Ref: Grab, Foodpanda, E-Gets Cambodia
 */
enum OrderStatusEnum: string
{
    // Customer placed order, waiting for merchant
    case Pending = 'pending';

    // Merchant accepted the order
    case Confirmed = 'confirmed';

    // Kitchen/warehouse preparing the order
    case Preparing = 'preparing';

    // Ready for pickup (by rider or customer)
    case Ready = 'ready';

    // Rider/delivery on the way
    case Delivering = 'delivering';

    // Customer received the order
    case Delivered = 'delivered';

    // Order finished, payment confirmed
    case Completed = 'completed';

    // Order cancelled
    case Cancelled = 'cancelled';

    // Money returned to customer
    case Refunded = 'refunded';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::Delivering => 'Delivering',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Get Khmer label for the status.
     */
    public function labelKh(): string
    {
        return match ($this) {
            self::Pending => 'រង់ចាំ',
            self::Confirmed => 'បានទទួល',
            self::Preparing => 'កំពុងរៀបចំ',
            self::Ready => 'រួចរាល់',
            self::Delivering => 'កំពុងដឹក',
            self::Delivered => 'បានដឹកជូន',
            self::Completed => 'បានបញ្ចប់',
            self::Cancelled => 'បានលុបចោល',
            self::Refunded => 'បានសងប្រាក់វិញ',
        };
    }

    /**
     * Get the badge variant for the status.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'outline',
            self::Confirmed => 'secondary',
            self::Preparing => 'default',
            self::Ready => 'default',
            self::Delivering => 'default',
            self::Delivered => 'success',
            self::Completed => 'success',
            self::Cancelled => 'destructive',
            self::Refunded => 'outline',
        };
    }

    /**
     * Get the icon for the status.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'Clock',
            self::Confirmed => 'CheckCircle',
            self::Preparing => 'ChefHat',
            self::Ready => 'Package',
            self::Delivering => 'Truck',
            self::Delivered => 'PackageCheck',
            self::Completed => 'CircleCheck',
            self::Cancelled => 'XCircle',
            self::Refunded => 'RotateCcw',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Confirmed => 'blue',
            self::Preparing => 'yellow',
            self::Ready => 'purple',
            self::Delivering => 'indigo',
            self::Delivered => 'green',
            self::Completed => 'green',
            self::Cancelled => 'red',
            self::Refunded => 'orange',
        };
    }

    /**
     * Get all statuses as options for select inputs.
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
     * Get all status values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get next allowed statuses (workflow transitions).
     *
     * Note: Pending can go directly to Preparing (auto-confirm for efficiency).
     * This is common in food delivery apps like Grab/Foodpanda.
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            // Pending can go to Confirmed, Preparing (auto-confirm), or Cancelled
            self::Pending => [self::Confirmed, self::Preparing, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Ready, self::Cancelled],
            self::Ready => [self::Delivering, self::Cancelled],
            self::Delivering => [self::Delivered, self::Cancelled],
            self::Delivered => [self::Completed, self::Refunded],
            self::Completed => [self::Refunded],
            self::Cancelled => [],
            self::Refunded => [],
        };
    }

    /**
     * Check if can transition to another status.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses());
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Confirmed,
            self::Preparing,
            self::Ready,
            self::Delivering,
        ]);
    }

    /**
     * Check if order can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return in_array($this, [self::Delivered, self::Completed]);
    }

    /**
     * Check if the status is a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Refunded]);
    }

    /**
     * Check if order is active (not final).
     */
    public function isActive(): bool
    {
        return !$this->isFinal();
    }

    /**
     * Get progress percentage for UI.
     */
    public function progressPercent(): int
    {
        return match ($this) {
            self::Pending => 10,
            self::Confirmed => 20,
            self::Preparing => 40,
            self::Ready => 60,
            self::Delivering => 80,
            self::Delivered => 95,
            self::Completed => 100,
            self::Cancelled => 0,
            self::Refunded => 100,
        };
    }

    /**
     * Get customer-facing message.
     */
    public function customerMessage(): string
    {
        return match ($this) {
            self::Pending => 'Your order is being reviewed by the merchant.',
            self::Confirmed => 'Order confirmed! Preparing your order.',
            self::Preparing => 'Your order is being prepared.',
            self::Ready => 'Your order is ready for pickup/delivery.',
            self::Delivering => 'Your order is on the way!',
            self::Delivered => 'Your order has been delivered.',
            self::Completed => 'Thank you for your order!',
            self::Cancelled => 'Your order has been cancelled.',
            self::Refunded => 'Refund processed successfully.',
        };
    }
}
