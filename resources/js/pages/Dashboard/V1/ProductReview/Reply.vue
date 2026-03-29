<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import { ModalForm } from '@/components/shared';
import { useTranslation } from '@/composables/useTranslation';
import { toast } from 'vue-sonner';
import { ReplyFormWidget } from '@modules/Order/resources/js/Components/Dashboard/V1/Widgets/ReplyFormWidget';

const { __ } = useTranslation();
const { show, close, redirect } = useModal();

interface ProductReviewItem {
    id: number;
    uuid: string;
    rating: number;
    comment: string | null;
    reply: string | null;
    replied_at: string | null;
    is_active: boolean;
    is_verified: boolean;
    created_at: string;
    formatted_date: string;
    customer?: { id: number; name: string; avatar?: string };
    product?: { id: number; name: string; sku?: string };
}

const props = defineProps<{
    review: ProductReviewItem;
}>();

const isOpen = computed({
    get: () => show.value,
    set: (val: boolean) => {
        if (!val) {
            close();
            redirect();
        }
    },
});

const form = useForm({
    reply: props.review.reply || '',
});

const maxLength = 500;
const isOverLimit = computed(() => form.reply.length > maxLength);
const isFormInvalid = computed(() => !form.reply.trim() || isOverLimit.value);
const isEditing = computed(() => !!props.review.reply);

const handleSubmit = () => {
    if (isFormInvalid.value) return;

    form.post(`/dashboard/product-reviews/${props.review.id}/reply`, {
        onSuccess: () => {
            toast.success(isEditing.value ? __('Reply updated successfully') : __('Reply sent successfully'));
            setTimeout(() => {
                close();
                redirect();
            }, 100);
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
        :title="isEditing ? __('Edit Reply') : __('Reply to Review')"
        :description="__('Respond to customer feedback')"
        :mode="isEditing ? 'edit' : 'create'"
        size="lg"
        :submit-text="isEditing ? __('Update Reply') : __('Send Reply')"
        :loading="form.processing"
        :disabled="isFormInvalid"
        @submit="handleSubmit"
        @cancel="handleCancel"
    >
        <ReplyFormWidget
            :review="review"
            v-model:reply="form.reply"
            :errors="form.errors"
            :max-length="maxLength"
            type="product"
        />
    </ModalForm>
</template>
