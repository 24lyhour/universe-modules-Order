<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { ModalForm } from '@/components/shared';
import ShippingZoneForm from '../../../../Components/Dashboard/V1/ShippingZoneForm.vue';
import type { ShippingZoneEditProps, ShippingZoneFormData } from '@order/types';
const props = defineProps<ShippingZoneEditProps>();

const isOpen = ref(true);
const shippingZoneFormRef = ref<InstanceType<typeof ShippingZoneForm> | null>(null);

const form = useForm<ShippingZoneFormData>({
    outlet_id: props.shippingZone.outlet_id,
    name: props.shippingZone.name,
    description: props.shippingZone.description || '',
    color: props.shippingZone.color,
    zone_type: props.shippingZone.zone_type,
    latitude: props.shippingZone.latitude,
    longitude: props.shippingZone.longitude,
    radius: props.shippingZone.radius || 1000,
    polygon_coordinates: props.shippingZone.polygon_coordinates,
    delivery_fee: props.shippingZone.delivery_fee,
    min_order_amount: props.shippingZone.min_order_amount,
    free_delivery_threshold: props.shippingZone.free_delivery_threshold,
    peak_hour_surcharge: props.shippingZone.peak_hour_surcharge,
    price_per_km: props.shippingZone.price_per_km,
    max_orders_per_hour: props.shippingZone.max_orders_per_hour,
    max_weight_kg: props.shippingZone.max_weight_kg,
    max_items: props.shippingZone.max_items,
    vehicle_type: props.shippingZone.vehicle_type,
    driver_requirements: props.shippingZone.driver_requirements || '',
    requires_special_handling: props.shippingZone.requires_special_handling,
    estimated_delivery_minutes: props.shippingZone.estimated_delivery_minutes,
    operating_hours: props.shippingZone.operating_hours,
    peak_hours: props.shippingZone.peak_hours,
    blocked_dates: props.shippingZone.blocked_dates,
    is_active: props.shippingZone.is_active,
    priority: props.shippingZone.priority,
});

// Check if selected outlet has location
const selectedOutletHasLocation = computed(() => {
    if (!form.outlet_id) return false;
    const outlet = props.outlets.find(o => o.id === form.outlet_id);
    return outlet?.latitude != null && outlet?.longitude != null;
});

// Check if form is invalid (required fields not filled or zone coordinates missing)
const isFormInvalid = computed(() => {
    // Check required fields
    if (!form.outlet_id || !form.name.trim() || !form.zone_type || !form.vehicle_type) {
        return true;
    }
    // Check if outlet has location set
    if (!selectedOutletHasLocation.value) {
        return true;
    }
    // Check zone coordinates based on zone type
    if (form.zone_type === 'circle') {
        return form.latitude === null || form.longitude === null;
    }
    if (form.zone_type === 'polygon') {
        return !form.polygon_coordinates ||
               !Array.isArray(form.polygon_coordinates) ||
               form.polygon_coordinates.length < 3;
    }
    return true;
});

const handleClose = () => {
    isOpen.value = false;
    setTimeout(() => {
        router.visit('/dashboard/shipping-zones');
    }, 200);
};

const handleSubmit = () => {
    form.put(`/dashboard/shipping-zones/${props.shippingZone.uuid}`, {
        onSuccess: () => {
            handleClose();
        },
    });
};
</script>

<template>
    <ModalForm
        v-model:open="isOpen"
        title="Edit Shipping Zone"
        :description="`Update '${shippingZone.name}'`"
        size="2xl"
        :loading="form.processing"
        :disabled="isFormInvalid"
        submit-text="Update Zone"
        @submit="handleSubmit"
        @cancel="handleClose"
    >
        <ShippingZoneForm
            ref="shippingZoneFormRef"
            v-model="form"
            :outlets="outlets"
            :zone-types="zoneTypes"
            :vehicle-types="vehicleTypes"
            :errors="form.errors"
        />
    </ModalForm>
</template>
