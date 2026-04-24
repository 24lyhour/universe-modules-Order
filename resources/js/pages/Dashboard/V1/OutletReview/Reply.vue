<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import { ModalForm } from '@/components/shared';
import { useTranslation } from '@/composables/useTranslation';
import { toast } from 'vue-sonner';
import { ReplyFormWidget } from '@order/Components/Dashboard/v1/Widgets/ReplyFormWidget';

const { __ } = useTranslation();
const { show, close, redirect } = useModal();

interface OutletReviewItem {
    id: number;
    uuid: string;
    overall_rating: number;
    food_rating: number | null;
    service_rating: number | null;
    delivery_rating: number | null;
    packaging_rating: number | null;
    comment: string | null;
    tags: string[];
    reply: string | null;
    replied_at: string | null;
    is_active: boolean;
    is_verified: boolean;
    created_at: string;
    formatted_date: string;
    tag_labels: string[];
    customer?: { id: number; name: string; avatar?: string };
    outlet?: { id: number; name: string };
}

const props = defineProps<{
    review: OutletReviewItem;
    availableTags?: Record<string, string>;
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

    form.post(`/dashboard/outlet-reviews/${props.review.id}/reply`, {
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
            type="outlet"
        />
    </ModalForm>
</template>
