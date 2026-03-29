<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { type VNode } from 'vue';
import {
    MapPin,
    Pencil,
    ArrowLeft,
    DollarSign,
    Truck,
    Clock,
    Circle,
    Hexagon,
    Package,
    Scale,
    Timer,
    AlertCircle,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { GeofenceMap } from '@/components/shared';
import type { ShippingZoneShowProps } from '@order/types';

defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Shipping Zones', href: '/dashboard/shipping-zones' },
            { title: 'View Zone', href: '#' },
        ]}, () => page),
});

const props = defineProps<ShippingZoneShowProps>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatRadius = (radius: number | null) => {
    if (!radius) return '-';
    return radius >= 1000 ? `${(radius / 1000).toFixed(1)} km` : `${radius} m`;
};

const handleEdit = () => {
    router.visit(`/dashboard/shipping-zones/${props.shippingZone.uuid}/edit`);
};

const handleBack = () => {
    router.visit('/dashboard/shipping-zones');
};
</script>

<template>
    <Head :title="`${shippingZone.name} - Shipping Zone`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="handleBack">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="flex items-center gap-3">
                    <div
                        class="w-4 h-4 rounded-full"
                        :style="{ backgroundColor: shippingZone.color }"
                    />
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ shippingZone.name }}</h1>
                        <p class="text-muted-foreground">
                            {{ shippingZone.outlet?.name || 'No outlet assigned' }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Badge :variant="shippingZone.is_active ? 'default' : 'secondary'" class="text-sm">
                    {{ shippingZone.is_active ? 'Active' : 'Inactive' }}
                </Badge>
                <Button @click="handleEdit" class="gap-2">
                    <Pencil class="h-4 w-4" />
                    Edit Zone
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Left Column: Map -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Map Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <MapPin class="h-4 w-4" />
                            Delivery Zone Area
                        </CardTitle>
                        <CardDescription>
                            {{ shippingZone.zone_type === 'circle' ? 'Circle zone' : 'Polygon zone' }}
                            <span v-if="shippingZone.zone_type === 'circle' && shippingZone.radius">
                                with {{ formatRadius(shippingZone.radius) }} radius
                            </span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <GeofenceMap
                            height="500px"
                            :readonly="true"
                            :geofence-type="shippingZone.zone_type"
                            :latitude="shippingZone.latitude"
                            :longitude="shippingZone.longitude"
                            :radius="shippingZone.radius || 1000"
                            :polygon-coordinates="shippingZone.polygon_coordinates"
                        />
                    </CardContent>
                </Card>

                <!-- Description Card -->
                <Card v-if="shippingZone.description">
                    <CardHeader>
                        <CardTitle class="text-base">Description</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="prose prose-sm max-w-none" v-html="shippingZone.description" />
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Details -->
            <div class="space-y-6">
                <!-- Zone Info Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Circle v-if="shippingZone.zone_type === 'circle'" class="h-4 w-4" />
                            <Hexagon v-else class="h-4 w-4" />
                            Zone Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Zone Type</span>
                            <Badge variant="outline" class="capitalize">{{ shippingZone.zone_type }}</Badge>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Priority</span>
                            <Badge variant="secondary">{{ shippingZone.priority }}</Badge>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Outlet</span>
                            <span class="text-sm font-medium">{{ shippingZone.outlet?.name || '-' }}</span>
                        </div>
                        <template v-if="shippingZone.zone_type === 'circle'">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Radius</span>
                                <span class="text-sm font-medium">{{ formatRadius(shippingZone.radius) }}</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.zone_type === 'polygon' && shippingZone.polygon_coordinates">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Vertices</span>
                                <span class="text-sm font-medium">{{ shippingZone.polygon_coordinates.length }} points</span>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <!-- Pricing Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <DollarSign class="h-4 w-4" />
                            Pricing
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Delivery Fee</span>
                            <span class="text-sm font-medium">{{ formatCurrency(shippingZone.delivery_fee) }}</span>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Min Order</span>
                            <span class="text-sm font-medium">{{ formatCurrency(shippingZone.min_order_amount) }}</span>
                        </div>
                        <template v-if="shippingZone.free_delivery_threshold">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Free Delivery Above</span>
                                <span class="text-sm font-medium text-green-600">{{ formatCurrency(shippingZone.free_delivery_threshold) }}</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.peak_hour_surcharge > 0">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Peak Hour Surcharge</span>
                                <span class="text-sm font-medium text-orange-600">+{{ formatCurrency(shippingZone.peak_hour_surcharge) }}</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.price_per_km">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Price per KM</span>
                                <span class="text-sm font-medium">{{ formatCurrency(shippingZone.price_per_km) }}</span>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <!-- Vehicle & Capacity Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Truck class="h-4 w-4" />
                            Vehicle & Capacity
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Vehicle Type</span>
                            <Badge variant="secondary" class="capitalize">{{ shippingZone.vehicle_type }}</Badge>
                        </div>
                        <template v-if="shippingZone.estimated_delivery_minutes">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Timer class="h-3.5 w-3.5" />
                                    Est. Delivery
                                </span>
                                <span class="text-sm font-medium">~{{ shippingZone.estimated_delivery_minutes }} min</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.max_orders_per_hour">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Clock class="h-3.5 w-3.5" />
                                    Max Orders/Hour
                                </span>
                                <span class="text-sm font-medium">{{ shippingZone.max_orders_per_hour }}</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.max_weight_kg">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Scale class="h-3.5 w-3.5" />
                                    Max Weight
                                </span>
                                <span class="text-sm font-medium">{{ shippingZone.max_weight_kg }} kg</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.max_items">
                            <Separator />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground flex items-center gap-1">
                                    <Package class="h-3.5 w-3.5" />
                                    Max Items
                                </span>
                                <span class="text-sm font-medium">{{ shippingZone.max_items }}</span>
                            </div>
                        </template>
                        <template v-if="shippingZone.requires_special_handling">
                            <Separator />
                            <div class="flex items-center gap-2 text-amber-600">
                                <AlertCircle class="h-4 w-4" />
                                <span class="text-sm font-medium">Requires Special Handling</span>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <!-- Driver Requirements Card -->
                <Card v-if="shippingZone.driver_requirements">
                    <CardHeader>
                        <CardTitle class="text-base">Driver Requirements</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">{{ shippingZone.driver_requirements }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
