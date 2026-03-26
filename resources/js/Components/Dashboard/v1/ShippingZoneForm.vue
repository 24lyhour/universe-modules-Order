<script setup lang="ts">
import { computed, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { GeofenceMap } from '@/components/shared';
import {
    MapPin,
    DollarSign,
    Truck,
    Settings,
} from 'lucide-vue-next';
import type { ShippingZoneFormData, OutletInfo, ZoneType, VehicleType } from '@order/types';
import TiptapEditor from '@/components/TiptapEditor.vue';

interface Props {
    modelValue: ShippingZoneFormData;
    outlets: Array<OutletInfo & { latitude: number | null; longitude: number | null }>;
    zoneTypes: Record<string, string>;
    vehicleTypes: Record<string, string>;
    errors?: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: ShippingZoneFormData];
}>();

const form = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

// Directly mutate the Inertia form property to maintain reactivity
const updateField = <K extends keyof ShippingZoneFormData>(field: K, value: ShippingZoneFormData[K]) => {
    (props.modelValue as unknown as Record<string, unknown>)[field] = value;
};

// Get selected outlet for map center
const selectedOutlet = computed(() => {
    if (!form.value.outlet_id) return null;
    return props.outlets.find(o => o.id === form.value.outlet_id) ?? null;
});

// When outlet changes, center map on outlet location
watch(() => form.value.outlet_id, (newOutletId) => {
    if (newOutletId) {
        const outlet = props.outlets.find(o => o.id === newOutletId);
        if (outlet?.latitude && outlet?.longitude && !form.value.latitude) {
            updateField('latitude', outlet.latitude);
            updateField('longitude', outlet.longitude);
        }
    }
});

// Color presets for zones
const colorPresets = [
    '#3B82F6', // Blue
    '#10B981', // Green
    '#F59E0B', // Yellow
    '#EF4444', // Red
    '#8B5CF6', // Purple
    '#EC4899', // Pink
    '#06B6D4', // Cyan
    '#F97316', // Orange
];
</script>

<template>
    <div class="space-y-6">
        <!-- Basic Info -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <MapPin class="h-4 w-4" />
                    Zone Information
                </CardTitle>
                <CardDescription>Basic details about the delivery zone</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Outlet -->
                    <div class="space-y-2">
                        <Label for="outlet_id">Outlet *</Label>
                        <Select
                            :model-value="form.outlet_id?.toString() ?? ''"
                            @update:model-value="(v) => updateField('outlet_id', v ? Number(v) : null)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select outlet" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="outlet in outlets"
                                    :key="outlet.id"
                                    :value="outlet.id.toString()"
                                >
                                    {{ outlet.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors?.outlet_id" class="text-sm text-destructive">{{ errors.outlet_id }}</p>
                    </div>

                    <!-- Name -->
                    <div class="space-y-2">
                        <Label for="name">Zone Name *</Label>
                        <Input
                            id="name"
                            :model-value="form.name"
                            @update:model-value="(v: string | number) => updateField('name', String(v))"
                            placeholder="e.g., Downtown Area"
                        />
                        <p v-if="errors?.name" class="text-sm text-destructive">{{ errors.name }}</p>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <TiptapEditor
                        id="description"
                        :model-value="form.description"
                        @update:model-value="(v) => updateField('description', v)"
                        placeholder="Describe this delivery zone..."
                        rows="2"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Zone Type -->
                    <div class="space-y-2">
                        <Label for="zone_type">Zone Type *</Label>
                        <Select
                            :model-value="form.zone_type"
                            @update:model-value="(v) => updateField('zone_type', v as ZoneType)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(label, value) in zoneTypes"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Color -->
                    <div class="space-y-2">
                        <Label>Zone Color</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                type="color"
                                :model-value="form.color"
                                @update:model-value="(v: string | number) => updateField('color', String(v))"
                                class="w-12 h-9 p-1 cursor-pointer"
                            />
                            <div class="flex gap-1">
                                <button
                                    v-for="color in colorPresets"
                                    :key="color"
                                    type="button"
                                    class="w-6 h-6 rounded border-2 transition-transform hover:scale-110"
                                    :class="form.color === color ? 'border-primary' : 'border-transparent'"
                                    :style="{ backgroundColor: color }"
                                    @click="updateField('color', color)"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Priority -->
                    <div class="space-y-2">
                        <Label for="priority">Priority</Label>
                        <Input
                            id="priority"
                            type="number"
                            :model-value="form.priority"
                            @update:model-value="(v) => updateField('priority', Number(v) || 0)"
                            min="0"
                            placeholder="0 = highest"
                        />
                        <p class="text-xs text-muted-foreground">Lower number = higher priority</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Geofence Map -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <MapPin class="h-4 w-4" />
                    Delivery Zone Area
                </CardTitle>
                <CardDescription>
                    Draw the delivery zone on the map. {{ form.zone_type === 'circle' ? 'Click to set center and adjust radius.' : 'Click to draw polygon points.' }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <GeofenceMap
                    height="550px"
                    :geofence-type="form.zone_type"
                    :latitude="form.latitude"
                    :longitude="form.longitude"
                    :radius="form.radius"
                    :polygon-coordinates="form.polygon_coordinates"
                    :reference-latitude="selectedOutlet?.latitude ? Number(selectedOutlet.latitude) : null"
                    :reference-longitude="selectedOutlet?.longitude ? Number(selectedOutlet.longitude) : null"
                    @update:latitude="(v) => updateField('latitude', v)"
                    @update:longitude="(v) => updateField('longitude', v)"
                    @update:radius="(v) => updateField('radius', v)"
                    @update:polygon-coordinates="(v) => updateField('polygon_coordinates', v)"
                    @update:geofence-type="(v) => updateField('zone_type', v as ZoneType)"
                />
                <p v-if="errors?.latitude || errors?.longitude" class="text-sm text-destructive mt-2">
                    {{ errors.latitude || errors.longitude }}
                </p>
            </CardContent>
        </Card>

        <!-- Pricing -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <DollarSign class="h-4 w-4" />
                    Pricing
                </CardTitle>
                <CardDescription>Set delivery fees and pricing rules</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <Label for="delivery_fee">Delivery Fee ($)</Label>
                        <Input
                            id="delivery_fee"
                            type="number"
                            step="0.01"
                            :model-value="form.delivery_fee"
                            @update:model-value="(v) => updateField('delivery_fee', Number(v) || 0)"
                            min="0"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="min_order_amount">Min Order Amount ($)</Label>
                        <Input
                            id="min_order_amount"
                            type="number"
                            step="0.01"
                            :model-value="form.min_order_amount"
                            @update:model-value="(v) => updateField('min_order_amount', Number(v) || 0)"
                            min="0"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="free_delivery_threshold">Free Delivery Above ($)</Label>
                        <Input
                            id="free_delivery_threshold"
                            type="number"
                            step="0.01"
                            :model-value="form.free_delivery_threshold ?? ''"
                            @update:model-value="(v) => updateField('free_delivery_threshold', v ? Number(v) : null)"
                            min="0"
                            placeholder="Optional"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="peak_hour_surcharge">Peak Hour Surcharge ($)</Label>
                        <Input
                            id="peak_hour_surcharge"
                            type="number"
                            step="0.01"
                            :model-value="form.peak_hour_surcharge"
                            @update:model-value="(v) => updateField('peak_hour_surcharge', Number(v) || 0)"
                            min="0"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="price_per_km">Price Per KM ($)</Label>
                        <Input
                            id="price_per_km"
                            type="number"
                            step="0.01"
                            :model-value="form.price_per_km ?? ''"
                            @update:model-value="(v) => updateField('price_per_km', v ? Number(v) : null)"
                            min="0"
                            placeholder="Optional (dynamic pricing)"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Capacity & Vehicle -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Truck class="h-4 w-4" />
                    Capacity & Vehicle
                </CardTitle>
                <CardDescription>Set capacity limits and vehicle requirements</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <Label for="vehicle_type">Vehicle Type *</Label>
                        <Select
                            :model-value="form.vehicle_type"
                            @update:model-value="(v) => updateField('vehicle_type', v as VehicleType)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select vehicle" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(label, value) in vehicleTypes"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="max_orders_per_hour">Max Orders/Hour</Label>
                        <Input
                            id="max_orders_per_hour"
                            type="number"
                            :model-value="form.max_orders_per_hour ?? ''"
                            @update:model-value="(v) => updateField('max_orders_per_hour', v ? Number(v) : null)"
                            min="1"
                            placeholder="Unlimited"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="max_weight_kg">Max Weight (kg)</Label>
                        <Input
                            id="max_weight_kg"
                            type="number"
                            step="0.1"
                            :model-value="form.max_weight_kg ?? ''"
                            @update:model-value="(v) => updateField('max_weight_kg', v ? Number(v) : null)"
                            min="0"
                            placeholder="Unlimited"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="max_items">Max Items</Label>
                        <Input
                            id="max_items"
                            type="number"
                            :model-value="form.max_items ?? ''"
                            @update:model-value="(v) => updateField('max_items', v ? Number(v) : null)"
                            min="1"
                            placeholder="Unlimited"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="estimated_delivery_minutes">Est. Delivery (min)</Label>
                        <Input
                            id="estimated_delivery_minutes"
                            type="number"
                            :model-value="form.estimated_delivery_minutes ?? ''"
                            @update:model-value="(v) => updateField('estimated_delivery_minutes', v ? Number(v) : null)"
                            min="1"
                            placeholder="e.g., 30"
                        />
                    </div>

                    <div class="flex items-center space-x-2 pt-6">
                        <Switch
                            :checked="form.requires_special_handling"
                            @update:checked="(v: boolean) => updateField('requires_special_handling', v)"
                        />
                        <Label>Requires Special Handling</Label>
                    </div>
                </div>

                <Separator class="my-4" />

                <div class="space-y-2">
                    <Label for="driver_requirements">Driver Requirements</Label>
                    <Textarea
                        id="driver_requirements"
                        :model-value="form.driver_requirements"
                        @update:model-value="(v) => updateField('driver_requirements', v)"
                        placeholder="Special requirements for drivers (e.g., must have insulated bag, valid license...)"
                        rows="2"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Status -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Settings class="h-4 w-4" />
                    Status
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-center space-x-2">
                    <Switch
                        :checked="form.is_active"
                        @update:checked="(v: boolean) => updateField('is_active', v)"
                    />
                    <Label>Zone is Active</Label>
                </div>
                <p class="text-sm text-muted-foreground mt-1">
                    Inactive zones will not be used for delivery calculations.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
