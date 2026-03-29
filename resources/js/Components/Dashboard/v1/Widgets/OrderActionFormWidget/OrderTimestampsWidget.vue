<script setup lang="ts">
import { CalendarDays, Truck, PackageCheck, XCircle, Clock } from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';

const { __ } = useTranslation();

defineProps<{
    createdAt?: string;
    shippedAt?: string | null;
    deliveredAt?: string | null;
    cancelledAt?: string | null;
    completedAt?: string | null;
}>();

// Format date
const formatDate = (date: string | null | undefined) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
            <Clock class="h-4 w-4" />
            {{ __('Timeline') }}
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div v-if="createdAt" class="flex items-center gap-2 p-2 rounded-lg bg-muted/50">
                <CalendarDays class="h-4 w-4 text-blue-500 shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground">{{ __('Created') }}</p>
                    <p class="font-medium truncate">{{ formatDate(createdAt) }}</p>
                </div>
            </div>

            <div v-if="shippedAt" class="flex items-center gap-2 p-2 rounded-lg bg-muted/50">
                <Truck class="h-4 w-4 text-purple-500 shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground">{{ __('Shipped') }}</p>
                    <p class="font-medium truncate">{{ formatDate(shippedAt) }}</p>
                </div>
            </div>

            <div v-if="deliveredAt" class="flex items-center gap-2 p-2 rounded-lg bg-muted/50">
                <PackageCheck class="h-4 w-4 text-green-500 shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground">{{ __('Delivered') }}</p>
                    <p class="font-medium truncate">{{ formatDate(deliveredAt) }}</p>
                </div>
            </div>

            <div v-if="completedAt" class="flex items-center gap-2 p-2 rounded-lg bg-green-50 dark:bg-green-900/20">
                <PackageCheck class="h-4 w-4 text-green-600 shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs text-green-600">{{ __('Completed') }}</p>
                    <p class="font-medium truncate text-green-700 dark:text-green-400">{{ formatDate(completedAt) }}</p>
                </div>
            </div>

            <div v-if="cancelledAt" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 dark:bg-red-900/20">
                <XCircle class="h-4 w-4 text-red-500 shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs text-red-600">{{ __('Cancelled') }}</p>
                    <p class="font-medium truncate text-red-700 dark:text-red-400">{{ formatDate(cancelledAt) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
