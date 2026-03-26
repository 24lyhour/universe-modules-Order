<?php

namespace Modules\Order\Enums;

/**
 * Delivery Vehicle Type Enum
 *
 * Defines the types of vehicles used for delivery.
 * Common in Cambodia: bike, motorcycle (most common), car, van, truck
 */
enum VehicleTypeEnum: string
{
    case Bike = 'bike';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case Van = 'van';
    case Truck = 'truck';

    /**
     * Get the label for the vehicle type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bike => 'Bicycle',
            self::Motorcycle => 'Motorcycle',
            self::Car => 'Car',
            self::Van => 'Van',
            self::Truck => 'Truck',
        };
    }

    /**
     * Get Khmer label for the vehicle type.
     */
    public function labelKh(): string
    {
        return match ($this) {
            self::Bike => 'កង់',
            self::Motorcycle => 'ម៉ូតូ',
            self::Car => 'ឡាន',
            self::Van => 'វ៉ាន',
            self::Truck => 'ឡានដឹកទំនិញ',
        };
    }

    /**
     * Get the icon for the vehicle type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Bike => 'Bike',
            self::Motorcycle => 'Bike',
            self::Car => 'Car',
            self::Van => 'Truck',
            self::Truck => 'Truck',
        };
    }

    /**
     * Get max weight capacity in kg.
     */
    public function maxWeightKg(): float
    {
        return match ($this) {
            self::Bike => 10,
            self::Motorcycle => 20,
            self::Car => 100,
            self::Van => 500,
            self::Truck => 2000,
        };
    }

    /**
     * Get estimated max items.
     */
    public function maxItems(): int
    {
        return match ($this) {
            self::Bike => 5,
            self::Motorcycle => 10,
            self::Car => 30,
            self::Van => 100,
            self::Truck => 500,
        };
    }

    /**
     * Check if vehicle is suitable for food delivery.
     */
    public function isSuitableForFood(): bool
    {
        return in_array($this, [self::Bike, self::Motorcycle, self::Car]);
    }

    /**
     * Check if vehicle is suitable for large items.
     */
    public function isSuitableForLargeItems(): bool
    {
        return in_array($this, [self::Van, self::Truck]);
    }

    /**
     * Get all vehicle types as options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }

    /**
     * Get all vehicle type values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
