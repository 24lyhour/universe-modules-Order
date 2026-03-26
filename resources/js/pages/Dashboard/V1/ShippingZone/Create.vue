<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { ModalForm } from '@/components/shared';
import ShippingZoneForm from '../../../../Components/Dashboard/V1/ShippingZoneForm.vue';
import type { ShippingZoneCreateProps, ShippingZoneFormData } from '@order/types';
const props = defineProps<ShippingZoneCreateProps>();

const isOpen = ref(true);

const form = useForm<ShippingZoneFormData>({
    outlet_id: null,
    name: '',
    description: '',
    color: '#3B82F6',
    zone_type: 'circle',
    latitude: null,
    longitude: null,
    radius: 1000,
    polygon_coordinates: null,
    delivery_fee: 0,
    min_order_amount: 0,
    free_delivery_threshold: null,
    peak_hour_surcharge: 0,
    price_per_km: null,
    max_orders_per_hour: null,
    max_weight_kg: null,
    max_items: null,
    vehicle_type: 'motorcycle',
    driver_requirements: '',
    requires_special_handling: false,
    estimated_delivery_minutes: null,
    operating_hours: null,
    peak_hours: null,
    blocked_dates: null,
    is_active: true,
    priority: 0,
});

// Check if form is invalid (required fields not filled or zone coordinates missing)
const isFormInvalid = computed(() => {
    // Check required fields
    if (!form.outlet_id || !form.name.trim() || !form.zone_type || !form.vehicle_type) {
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
    form.post('/dashboard/shipping-zones', {
        onSuccess: () => {
            handleClose();
        },
    });
};
</script>

<template>
    <ModalForm
        v-model:open="isOpen"
        title="Create Shipping Zone"
        description="Define a new delivery zone for an outlet"
        size="2xl"
        :loading="form.processing"
        :disabled="isFormInvalid"
        submit-text="Create Zone"
        @submit="handleSubmit"
        @cancel="handleClose"
    >
        <ShippingZoneForm
            v-model="form"
            :outlets="outlets"
            :zone-types="zoneTypes"
            :vehicle-types="vehicleTypes"
            :errors="form.errors"
        />
    </ModalForm>
</template>
