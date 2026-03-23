<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Send, MessageSquare } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import TiptapEditor from '@/components/TiptapEditor.vue';

const { __ } = useTranslation();

interface Props {
    reviewId: number;
    reviewType: 'product' | 'outlet';
    existingReply?: string | null;
    repliedAt?: string | null;
    maxLength?: number;
}

const props = withDefaults(defineProps<Props>(), {
    existingReply: null,
    repliedAt: null,
    maxLength: 500,
});

const emit = defineEmits<{
    success: [];
    cancel: [];
}>();

const isSubmitting = ref(false);

const form = useForm({
    reply: props.existingReply || '',
});

const charCount = computed(() => form.reply.length);
const isOverLimit = computed(() => charCount.value > props.maxLength);
const canSubmit = computed(() => form.reply.trim().length > 0 && !isOverLimit.value && !isSubmitting.value);

const routePrefix = computed(() => {
    return props.reviewType === 'product' ? 'product-reviews' : 'outlet-reviews';
});

const handleSubmit = () => {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    form.post(`/dashboard/${routePrefix.value}/${props.reviewId}/reply`, {
        onSuccess: () => {
            emit('success');
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const handleCancel = () => {
    emit('cancel');
};

const formatDate = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center gap-2">
            <MessageSquare class="h-5 w-5 text-primary" />
            <h3 class="text-lg font-semibold">{{ __('Merchant Reply') }}</h3>
        </div>

        <!-- Existing Reply Display -->
        <div v-if="existingReply" class="p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border-l-4 border-blue-500">
            <p class="whitespace-pre-wrap text-sm">{{ existingReply }}</p>
            <p v-if="repliedAt" class="text-xs text-muted-foreground mt-2">
                {{ __('Replied on') }} {{ formatDate(repliedAt) }}
            </p>
        </div>

        <!-- Reply Form -->
        <div v-else class="space-y-4">
            <div class="space-y-2">
                <Label for="reply">{{ __('Your Reply') }}</Label>
                <TiptapEditor
                    id="reply"
                    v-model="form.reply"
                    :placeholder="__('Write your reply to the customer...')"
                    rows="5"
                    :class="{ 'border-destructive': isOverLimit }"
                />
                <div class="flex justify-between text-xs">
                    <span v-if="form.errors.reply" class="text-destructive">
                        {{ form.errors.reply }}
                    </span>
                    <span v-else class="text-muted-foreground">
                        {{ __('A thoughtful reply can improve customer satisfaction') }}
                    </span>
                    <span :class="isOverLimit ? 'text-destructive' : 'text-muted-foreground'">
                        {{ charCount }}/{{ maxLength }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <Button type="button" variant="outline" @click="handleCancel">
                    {{ __('Cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="!canSubmit"
                    @click="handleSubmit"
                >
                    <Send class="mr-2 h-4 w-4" />
                    {{ isSubmitting ? __('Sending...') : __('Send Reply') }}
                </Button>
            </div>
        </div>
    </div>
</template>
