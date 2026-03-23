<script setup lang="ts">
import { ModalForm } from '@/components/shared';
import CartForm from '@order/Components/Dashboard/V1/CartForm.vue';
import { useForm } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import { computed } from 'vue';
import type { CartFormData, CartCreateProps } from '@order/types';

const props = defineProps<CartCreateProps>();

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

const form = useForm<CartFormData>({
    customer_id: null,
    outlet_id: null,
    status: 'active',
    notes: '',
    expires_at: '',
    is_active: true,
});

// Check if form is valid (cart doesn't require specific fields)
const isFormInvalid = computed(() => {
    return false;
});

const handleSubmit = () => {
    form.post('/dashboard/carts', {
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
        title="Create Cart"
        description="Create a new shopping cart"
        mode="create"
        size="lg"
        submit-text="Create"
        :loading="form.processing"
        :disabled="isFormInvalid"
        @submit="handleSubmit"
        @cancel="handleCancel"
    >
        <CartForm
            v-model="form"
            mode="create"
            :customers="props.customers"
            :outlets="props.outlets"
            :statuses="props.statuses"
        />
    </ModalForm>
</template>
