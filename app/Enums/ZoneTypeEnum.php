<?php

namespace Modules\Order\Enums;

/**
 * Shipping Zone Type Enum
 *
 * Defines the shape/type of delivery zones:
 * - Circle: Radius-based zone from a center point
 * - Polygon: Custom drawn polygon area
 */
enum ZoneTypeEnum: string
{
    case Circle = 'circle';
    case Polygon = 'polygon';

    /**
     * Get the label for the zone type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Circle => 'Circle',
            self::Polygon => 'Polygon',
        };
    }

    /**
     * Get Khmer label for the zone type.
     */
    public function labelKh(): string
    {
        return match ($this) {
            self::Circle => 'រង្វង់',
            self::Polygon => 'ពហុកោណ',
        };
    }

    /**
     * Get the icon for the zone type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Circle => 'Circle',
            self::Polygon => 'Hexagon',
        };
    }

    /**
     * Get description for the zone type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Circle => 'Radius-based zone from a center point',
            self::Polygon => 'Custom drawn polygon area',
        };
    }

    /**
     * Get all zone types as options for select inputs.
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
     * Get all zone type values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
