<script setup lang="ts">
import { ModalForm } from '@/components/shared';
import OrderForm from '@order/Components/Dashboard/V1/OrderForm.vue';
import { useForm } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import { computed } from 'vue';
import type { OrderFormData, OrderCreateProps } from '@order/types';

const props = defineProps<OrderCreateProps>();

const { show, close, redirect } = useModal();

const isOpen = computed({
    get: () => show.value,
    set: (val: boolean) => {
        if (!val) {
            close();
            redirect();
        }
    },
});

const form = useForm<OrderFormData>({
    customer_id: null,
    outlet_id: null,
    subtotal: 0,
    discount_amount: 0,
    tax_amount: 0,
    total_amount: 0,
    status: 'pending',
    payment_status: 'pending',
    payment_method: '',
    notes: '',
});

// Check if form is valid
const isFormInvalid = computed(() => {
    return form.subtotal <= 0;
});

const handleSubmit = () => {
    form.post('/dashboard/orders', {
        onSuccess: () => {
            close();
            redirect();
        },
    });
};

const handleCancel = () => {
    close();
    redirect();
};
</script>

<template>
    <ModalForm
        v-model:open="isOpen"
        title="Create Order"
        description="Create a new order manually"
        mode="create"
        size="2xl"
        submit-text="Create"
        :loading="form.processing"
        :disabled="isFormInvalid"
        @submit="handleSubmit"
        @cancel="handleCancel"
    >
        <OrderForm
            v-model="form"
            mode="create"
            :customers="props.customers"
            :outlets="props.outlets"
            :statuses="props.statuses"
            :payment-statuses="props.paymentStatuses"
        />
    </ModalForm>
</template>
