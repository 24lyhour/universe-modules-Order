<script setup lang="ts">
import { computed } from 'vue';
import {
    CheckCircle,
    Package,
    Truck,
    Clock,
    ChefHat,
    PackageCheck,
    XCircle,
    RotateCcw,
} from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';

const { __ } = useTranslation();

const props = defineProps<{
    status: string;
}>();

// Progress tracker steps (main workflow only)
const progressSteps = [
    { key: 'pending', label: 'Pending', icon: Clock },
    { key: 'confirmed', label: 'Confirmed', icon: CheckCircle },
    { key: 'preparing', label: 'Preparing', icon: ChefHat },
    { key: 'ready', label: 'Ready', icon: Package },
    { key: 'delivering', label: 'Delivering', icon: Truck },
    { key: 'delivered', label: 'Delivered', icon: PackageCheck },
    { key: 'completed', label: 'Completed', icon: CheckCircle },
];

// Get the index of current status in the workflow
const getStatusIndex = (status: string): number => {
    return progressSteps.findIndex(step => step.key === status);
};

// Computed property for current step index
const currentStepIndex = computed(() => {
    return getStatusIndex(props.status);
});

// Check if order is in a terminal non-standard state
const isTerminalState = computed(() => {
    return ['cancelled', 'refunded'].includes(props.status);
});
</script>

<template>
    <div class="space-y-2">
        <span class="text-sm font-medium text-muted-foreground">{{ __('Order Progress') }}</span>

        <!-- Cancelled/Refunded State -->
        <div v-if="isTerminalState" class="p-4 rounded-lg border-2 border-dashed"
            :class="status === 'cancelled' ? 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950' : 'border-orange-300 bg-orange-50 dark:border-orange-800 dark:bg-orange-950'">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full"
                    :class="status === 'cancelled' ? 'bg-red-100 dark:bg-red-900' : 'bg-orange-100 dark:bg-orange-900'">
                    <XCircle v-if="status === 'cancelled'" class="h-5 w-5 text-red-600 dark:text-red-400" />
                    <RotateCcw v-else class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <p class="font-medium" :class="status === 'cancelled' ? 'text-red-700 dark:text-red-300' : 'text-orange-700 dark:text-orange-300'">
                        {{ status === 'cancelled' ? __('Order Cancelled') : __('Order Refunded') }}
                    </p>
                    <p class="text-sm" :class="status === 'cancelled' ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400'">
                        {{ status === 'cancelled'
                            ? __('This order has been cancelled and cannot proceed.')
                            : __('This order has been refunded to the customer.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Normal Progress Flow -->
        <div v-else class="relative">
            <!-- Progress Steps -->
            <div class="flex items-center justify-between">
                <template v-for="(step, index) in progressSteps" :key="step.key">
                    <!-- Step Circle -->
                    <div class="flex flex-col items-center relative z-10">
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-all duration-300"
                            :class="{
                                'bg-primary border-primary text-primary-foreground': index < currentStepIndex,
                                'bg-primary border-primary text-primary-foreground ring-4 ring-primary/20': index === currentStepIndex,
                                'bg-muted border-muted-foreground/30 text-muted-foreground': index > currentStepIndex,
                            }"
                        >
                            <CheckCircle v-if="index < currentStepIndex" class="h-4 w-4" />
                            <component v-else :is="step.icon" class="h-4 w-4" />
                        </div>
                        <span
                            class="mt-1.5 text-[10px] font-medium text-center whitespace-nowrap"
                            :class="{
                                'text-primary': index <= currentStepIndex,
                                'text-muted-foreground': index > currentStepIndex,
                            }"
                        >
                            {{ step.label }}
                        </span>
                    </div>

                    <!-- Connector Line (not after last step) -->
                    <div
                        v-if="index < progressSteps.length - 1"
                        class="flex-1 h-0.5 mx-1 transition-all duration-300"
                        :class="{
                            'bg-primary': index < currentStepIndex,
                            'bg-muted-foreground/30': index >= currentStepIndex,
                        }"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
