<script setup lang="ts">
import { ModalForm } from '@/components/shared';
import CartForm from '@order/Components/Dashboard/V1/CartForm.vue';
import { useForm } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import { computed } from 'vue';
import type { CartFormData, CartEditProps } from '@order/types';

const props = defineProps<CartEditProps>();

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

const truncateUuid = (uuid: string) => {
    return uuid.substring(0, 8) + '...';
};

const form = useForm<CartFormData>({
    customer_id: props.cart.customer_id,
    outlet_id: props.cart.outlet_id,
    status: props.cart.status,
    notes: props.cart.notes || '',
    expires_at: props.cart.expires_at || '',
    is_active: props.cart.is_active,
});

// Check if form is valid
const isFormInvalid = computed(() => {
    return false;
});

const handleSubmit = () => {
    form.put(`/dashboard/carts/${props.cart.id}`, {
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
        :title="`Edit Cart ${truncateUuid(cart.uuid)}`"
        description="Update cart information"
        mode="edit"
        size="lg"
        submit-text="Save Changes"
        :loading="form.processing"
        :disabled="isFormInvalid"
        @submit="handleSubmit"
        @cancel="handleCancel"
    >
        <CartForm
            v-model="form"
            mode="edit"
            :customers="props.customers"
            :outlets="props.outlets"
            :statuses="props.statuses"
        />
    </ModalForm>
</template>
