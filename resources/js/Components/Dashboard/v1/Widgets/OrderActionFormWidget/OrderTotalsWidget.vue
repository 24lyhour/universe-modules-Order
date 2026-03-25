<script setup lang="ts">
import { computed } from 'vue';
import { Receipt, Tag, Percent, Truck, ShoppingBag, Minus, Plus, Equal } from 'lucide-vue-next';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/composables/useTranslation';

const { __ } = useTranslation();

const props = defineProps<{
    subtotal?: number;
    discountAmount?: number;
    taxAmount?: number;
    shippingCost?: number;
    totalAmount?: number;
    itemsCount?: number;
}>();

// Format currency
const formatCurrency = (amount: number | undefined) => {
    if (amount === undefined || amount === null) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Calculate tax percentage
const taxPercentage = computed(() => {
    if (!props.subtotal || !props.taxAmount || props.subtotal === 0) return null;
    return Math.round((props.taxAmount / props.subtotal) * 100);
});

// Calculate discount percentage
const discountPercentage = computed(() => {
    if (!props.subtotal || !props.discountAmount || props.subtotal === 0) return null;
    return Math.round((props.discountAmount / props.subtotal) * 100);
});

// Check if order has items
const hasItems = computed(() => props.itemsCount && props.itemsCount > 0);

// Calculate subtotal after discount
const subtotalAfterDiscount = computed(() => {
    const subtotal = props.subtotal || 0;
    const discount = props.discountAmount || 0;
    return subtotal - discount;
});
</script>

<template>
    <div class="space-y-3 pt-3 border-t">
        <!-- Header with items count -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <Receipt class="h-4 w-4" />
                {{ __('Order Summary') }}
            </div>
            <Badge v-if="itemsCount !== undefined" variant="outline" class="text-xs">
                {{ itemsCount }} {{ itemsCount === 1 ? __('item') : __('items') }}
            </Badge>
        </div>

        <!-- Summary Lines -->
        <div v-if="hasItems" class="space-y-2 text-sm">
            <!-- Subtotal (Items Total) -->
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground flex items-center gap-1.5">
                    <ShoppingBag class="h-3.5 w-3.5" />
                    {{ __('Subtotal') }}
                </span>
                <span class="tabular-nums">{{ formatCurrency(subtotal) }}</span>
            </div>

            <!-- Discount (Subtraction) -->
            <div v-if="discountAmount && discountAmount > 0" class="flex items-center justify-between text-green-600 dark:text-green-400">
                <span class="flex items-center gap-1.5">
                    <Minus class="h-3.5 w-3.5" />
                    {{ __('Discount') }}
                    <Badge v-if="discountPercentage" variant="outline" class="text-[10px] px-1 py-0 h-4 text-green-600 border-green-300 dark:border-green-700">
                        -{{ discountPercentage }}%
                    </Badge>
                </span>
                <span class="tabular-nums">-{{ formatCurrency(discountAmount) }}</span>
            </div>

            <!-- Subtotal After Discount (only show if there was discount) -->
            <div v-if="discountAmount && discountAmount > 0" class="flex items-center justify-between border-t border-dashed pt-2 mt-2">
                <span class="text-muted-foreground flex items-center gap-1.5 text-xs">
                    <Equal class="h-3 w-3" />
                    {{ __('After Discount') }}
                </span>
                <span class="tabular-nums font-medium">{{ formatCurrency(subtotalAfterDiscount) }}</span>
            </div>

            <!-- Tax (Addition) -->
            <div v-if="taxAmount !== undefined && taxAmount > 0" class="flex items-center justify-between">
                <span class="text-muted-foreground flex items-center gap-1.5">
                    <Plus class="h-3.5 w-3.5" />
                    {{ __('Tax') }}
                    <Badge v-if="taxPercentage" variant="outline" class="text-[10px] px-1 py-0 h-4">
                        {{ taxPercentage }}%
                    </Badge>
                </span>
                <span class="tabular-nums">+{{ formatCurrency(taxAmount) }}</span>
            </div>

            <!-- Shipping (Addition) -->
            <div v-if="shippingCost !== undefined && shippingCost > 0" class="flex items-center justify-between">
                <span class="text-muted-foreground flex items-center gap-1.5">
                    <Plus class="h-3.5 w-3.5" />
                    {{ __('Shipping') }}
                </span>
                <span class="tabular-nums">+{{ formatCurrency(shippingCost) }}</span>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="py-4 text-center">
            <p class="text-sm text-muted-foreground">{{ __('No items to calculate') }}</p>
        </div>

        <Separator />

        <!-- Grand Total -->
        <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-primary/5 border border-primary/20">
            <span class="font-semibold flex items-center gap-1.5">
                {{ __('Total') }}
            </span>
            <span class="font-bold text-xl tabular-nums text-primary">{{ formatCurrency(hasItems ? totalAmount : 0) }}</span>
        </div>
    </div>
</template>
