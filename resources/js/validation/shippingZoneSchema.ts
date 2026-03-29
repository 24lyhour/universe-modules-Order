import { z } from 'zod';

// Base schema for form validation (without refine for type compatibility)
export const shippingZoneSchema = z.object({
    outlet_id: z.number({ required_error: 'Outlet is required' }).nullable(),
    name: z.string().min(1, 'Zone name is required'),
    description: z.string().optional(),
    color: z.string().min(1, 'Color is required'),
    zone_type: z.enum(['circle', 'polygon'], { required_error: 'Zone type is required' }),
    latitude: z.number().nullable(),
    longitude: z.number().nullable(),
    radius: z.number().min(10, 'Radius must be at least 10 meters').nullable(),
    polygon_coordinates: z.array(z.tuple([z.number(), z.number()])).nullable(),
    delivery_fee: z.number().min(0, 'Delivery fee cannot be negative'),
    min_order_amount: z.number().min(0, 'Minimum order amount cannot be negative'),
    free_delivery_threshold: z.number().nullable(),
    peak_hour_surcharge: z.number().min(0, 'Peak hour surcharge cannot be negative'),
    price_per_km: z.number().nullable(),
    max_orders_per_hour: z.number().nullable(),
    max_weight_kg: z.number().nullable(),
    max_items: z.number().nullable(),
    vehicle_type: z.enum(['bike', 'motorcycle', 'car', 'van', 'truck'], { required_error: 'Vehicle type is required' }),
    driver_requirements: z.string().optional(),
    requires_special_handling: z.boolean(),
    estimated_delivery_minutes: z.number().nullable(),
    operating_hours: z.unknown().nullable(),
    peak_hours: z.unknown().nullable(),
    blocked_dates: z.unknown().nullable(),
    is_active: z.boolean(),
    priority: z.number().min(0, 'Priority cannot be negative'),
});

export type ShippingZoneSchemaType = z.infer<typeof shippingZoneSchema>;

/**
 * Check if the form has valid zone coordinates based on zone type
 */
export const hasValidZoneCoordinates = (data: Partial<ShippingZoneSchemaType>): boolean => {
    if (data.zone_type === 'circle') {
        return data.latitude !== null && data.longitude !== null;
    }
    if (data.zone_type === 'polygon') {
        return data.polygon_coordinates !== null &&
               Array.isArray(data.polygon_coordinates) &&
               data.polygon_coordinates.length >= 3;
    }
    return false;
};
