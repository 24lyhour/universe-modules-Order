<script setup lang="ts">
import { ModalForm } from '@/components/shared';
import OrderForm from '@order/Components/Dashboard/V1/OrderForm.vue';
import { useForm } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import { computed } from 'vue';
import type { OrderFormData, OrderEditProps } from '@order/types';

const props = defineProps<OrderEditProps>();

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
    customer_id: props.order.customer_id,
    outlet_id: props.order.outlet_id,
    subtotal: props.order.subtotal,
    discount_amount: props.order.discount_amount,
    tax_amount: props.order.tax_amount,
    total_amount: props.order.total_amount,
    status: props.order.status,
    payment_status: props.order.payment_status,
    payment_method: props.order.payment_method || '',
    notes: props.order.notes || '',
});

// Check if form is valid
const isFormInvalid = computed(() => {
    return form.subtotal <= 0;
});

const handleSubmit = () => {
    form.put(`/dashboard/orders/${props.order.uuid}`, {
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
        :title="`Edit Order ${order.order_number}`"
        description="Update order information"
        mode="edit"
        size="2xl"
        submit-text="Save Changes"
        :loading="form.processing"
        :disabled="isFormInvalid"
        @submit="handleSubmit"
        @cancel="handleCancel"
    >
        <OrderForm
            v-model="form"
            mode="edit"
            :customers="props.customers"
            :outlets="props.outlets"
            :statuses="props.statuses"
            :payment-statuses="props.paymentStatuses"
        />
    </ModalForm>
</template>
