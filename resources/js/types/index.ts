// Order types
export interface OrderItem {
    id: number;
    uuid: string;
    order_number: string;
    customer_id: number | null;
    outlet_id: number | null;
    cart_id: number | null;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total_amount: number;
    status: OrderStatus;
    payment_status: PaymentStatus;
    payment_method: string | null;
    notes: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    cancelled_at: string | null;
    created_at: string;
    updated_at: string;
    customer?: CustomerInfo | null;
    outlet?: OutletInfo | null;
    items?: OrderItemDetail[];
    items_count?: number;
    total_quantity?: number;
    shipping?: OrderShippingInfo | null;
}

// Partial order for action forms (accepts both full and minimal data)
export interface OrderActionData {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    uuid?: string;
    customer_id?: number | null;
    outlet_id?: number | null;
    cart_id?: number | null;
    subtotal?: number;
    discount_amount?: number;
    tax_amount?: number;
    shipping_cost?: number;
    total_amount?: number;
    payment_method?: string | null;
    notes?: string | null;
    shipped_at?: string | null;
    delivered_at?: string | null;
    cancelled_at?: string | null;
    completed_at?: string | null;
    created_at?: string;
    updated_at?: string;
    customer?: CustomerInfo | null;
    outlet?: OutletInfo | null;
    items?: OrderItemDetail[];
    items_count?: number;
    total_quantity?: number;
    shipping?: OrderShippingInfo | null;
}

// Order shipping info
export interface OrderShippingInfo {
    id: number;
    carrier?: string | null;
    method?: string | null;
    shipping_cost?: number;
    tracking_number?: string | null;
    recipient_name?: string | null;
    phone?: string | null;
    street_1?: string | null;
    street_2?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    country?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    estimated_delivery_at?: string | null;
    shipped_at?: string | null;
    delivered_at?: string | null;
}

export interface OrderItemDetail {
    id: number;
    order_id: number;
    product_id: number | null;
    product_name: string;
    product_sku: string | null;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    total_amount: number;
    notes: string | null;
    product?: ProductInfo | null;
}

export type OrderStatus = 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivering' | 'delivered' | 'completed' | 'cancelled' | 'refunded';
export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded' | 'partial';

// Cart types
export interface CartItem {
    id: number;
    uuid: string;
    customer_id: number | null;
    outlet_id: number | null;
    status: CartStatus;
    notes: string | null;
    expires_at: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    customer?: CustomerInfo | null;
    outlet?: OutletInfo | null;
    items?: CartItemDetail[];
    items_count?: number;
    total_amount?: number;
    total_quantity?: number;
}

export interface CartItemDetail {
    id: number;
    cart_id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    total_amount: number;
    notes: string | null;
    product?: ProductInfo | null;
}

export type CartStatus = 'active' | 'abandoned' | 'converted' | 'expired';

// Shared types
export interface CustomerInfo {
    id: number;
    name: string;
    email: string;
    phone?: string;
    address?: string | null;
}

export interface OutletInfo {
    id: number;
    name: string;
    address: string | null;
    phone: string | null;
    google_map_url: string | null;
    latitude: number | null;
    longitude: number | null;
}

export interface ProductInfo {
    id: number;
    name: string;
    sku?: string;
    price?: number;
    sale_price?: number | null;
    images?: string[];
}

export interface SelectOption {
    value: string;
    label: string;
}

// Pagination
export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
}

// Order Stats (Cambodia E-commerce Flow)
export interface OrderStats {
    total: number;
    pending: number;
    confirmed: number;
    preparing: number;
    ready: number;
    delivering: number;
    delivered: number;
    completed: number;
    cancelled: number;
    refunded: number;
    total_revenue: number;
    payment_pending: number;
    payment_paid: number;
}

// Cart Stats
export interface CartStats {
    total: number;
    active: number;
    abandoned: number;
    converted: number;
    expired: number;
}

// Props interfaces
export interface OrderIndexProps {
    orderItems: PaginatedResponse<OrderItem>;
    filters: {
        search?: string;
        status?: string;
        payment_status?: string;
        outlet_id?: number;
        customer_id?: number;
        date_from?: string;
        date_to?: string;
    };
    stats: OrderStats;
    outlets: OutletInfo[];
    statuses: SelectOption[];
    paymentStatuses: SelectOption[];
}

export interface CartIndexProps {
    cartItems: PaginatedResponse<CartItem>;
    filters: {
        search?: string;
        status?: string;
        is_active?: boolean;
        outlet_id?: number;
        customer_id?: number;
    };
    stats: CartStats;
    outlets: OutletInfo[];
    statuses: SelectOption[];
}

// Form data interfaces
export interface OrderFormData {
    customer_id: number | null;
    outlet_id: number | null;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total_amount: number;
    status: string;
    payment_status: string;
    payment_method: string;
    notes: string;
}

export interface CartFormData {
    customer_id: number | null;
    outlet_id: number | null;
    status: string;
    notes: string;
    expires_at: string;
    is_active: boolean;
}

// Create/Edit Props interfaces
export interface OrderCreateProps {
    outlets: OutletInfo[];
    customers: CustomerInfo[];
    statuses: SelectOption[];
    paymentStatuses: SelectOption[];
}

export interface OrderEditProps {
    order: OrderItem;
    outlets: OutletInfo[];
    customers: CustomerInfo[];
    statuses: SelectOption[];
    paymentStatuses: SelectOption[];
}

export interface CartCreateProps {
    outlets: OutletInfo[];
    customers: CustomerInfo[];
    statuses: SelectOption[];
}

export interface CartEditProps {
    cart: CartItem;
    outlets: OutletInfo[];
    customers: CustomerInfo[];
    statuses: SelectOption[];
}

// ==================== SHIPPING ZONE TYPES ====================

export type ZoneType = 'circle' | 'polygon';
export type VehicleType = 'bike' | 'motorcycle' | 'car' | 'van' | 'truck';

export interface ShippingZone {
    id: number;
    uuid: string;
    outlet_id: number;
    name: string;
    description: string | null;
    color: string;
    zone_type: ZoneType;
    latitude: number;
    longitude: number;
    radius: number | null;
    polygon_coordinates: [number, number][] | null;
    // Pricing
    delivery_fee: number;
    min_order_amount: number;
    free_delivery_threshold: number | null;
    peak_hour_surcharge: number;
    price_per_km: number | null;
    // Capacity
    max_orders_per_hour: number | null;
    max_weight_kg: number | null;
    max_items: number | null;
    // Vehicle
    vehicle_type: VehicleType;
    driver_requirements: string | null;
    requires_special_handling: boolean;
    // Time
    estimated_delivery_minutes: number | null;
    operating_hours: Record<string, { open: string; close: string }> | null;
    peak_hours: { start: string; end: string } | null;
    blocked_dates: string[] | null;
    // Status
    is_active: boolean;
    priority: number;
    // Relations
    outlet?: OutletInfo | null;
    // Timestamps
    created_at: string;
    updated_at: string;
}

export interface ShippingZoneFormData {
    outlet_id: number | null;
    name: string;
    description: string;
    color: string;
    zone_type: ZoneType;
    latitude: number | null;
    longitude: number | null;
    radius: number;
    polygon_coordinates: [number, number][] | null;
    // Pricing
    delivery_fee: number;
    min_order_amount: number;
    free_delivery_threshold: number | null;
    peak_hour_surcharge: number;
    price_per_km: number | null;
    // Capacity
    max_orders_per_hour: number | null;
    max_weight_kg: number | null;
    max_items: number | null;
    // Vehicle
    vehicle_type: VehicleType;
    driver_requirements: string;
    requires_special_handling: boolean;
    // Time
    estimated_delivery_minutes: number | null;
    operating_hours: Record<string, { open: string; close: string }> | null;
    peak_hours: { start: string; end: string } | null;
    blocked_dates: string[] | null;
    // Status
    is_active: boolean;
    priority: number;
}

export interface ShippingZoneStats {
    total: number;
    active: number;
    inactive: number;
    by_type: {
        circle: number;
        polygon: number;
    };
    by_vehicle: {
        bike: number;
        motorcycle: number;
        car: number;
        van: number;
        truck: number;
    };
}

export interface ShippingZoneIndexProps {
    shippingZones: PaginatedResponse<ShippingZone>;
    filters: {
        search?: string;
        outlet_id?: number;
        zone_type?: string;
        vehicle_type?: string;
        is_active?: string;
    };
    stats: ShippingZoneStats;
    outlets: OutletInfo[];
    zoneTypes: Record<string, string>;
    vehicleTypes: Record<string, string>;
}

export interface ShippingZoneCreateProps {
    outlets: Array<OutletInfo & { latitude: number | null; longitude: number | null }>;
    zoneTypes: Record<string, string>;
    vehicleTypes: Record<string, string>;
}

export interface ShippingZoneEditProps {
    shippingZone: ShippingZone;
    outlets: Array<OutletInfo & { latitude: number | null; longitude: number | null }>;
    zoneTypes: Record<string, string>;
    vehicleTypes: Record<string, string>;
}

export interface ShippingZoneShowProps {
    shippingZone: ShippingZone;
}

export interface DeliveryCheckResponse {
    available: boolean;
    message?: string;
    zone_name?: string;
    delivery_fee?: number;
    estimated_minutes?: number;
    min_order_amount?: number;
    free_delivery_threshold?: number;
    vehicle_type?: VehicleType;
    distance_km?: number;
}
