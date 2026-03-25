<script setup lang="ts">
import { ShoppingCart, Package, Tag, Percent, Info, X, Minus } from 'lucide-vue-next';
import { computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { OrderItemDetail } from '@order/types';

const { __ } = useTranslation();

const props = defineProps<{
    items: OrderItemDetail[];
}>();

// Format currency
const formatCurrency = (amount: number | undefined) => {
    if (amount === undefined) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Check if item has discount
const hasDiscount = (item: OrderItemDetail) => {
    return item.discount_amount && item.discount_amount > 0;
};

// Calculate original price (before discount)
const getOriginalTotal = (item: OrderItemDetail) => {
    return item.unit_price * item.quantity;
};

// Calculate discount percentage for item
const getDiscountPercentage = (item: OrderItemDetail) => {
    const originalTotal = getOriginalTotal(item);
    if (!originalTotal || !item.discount_amount) return null;
    return Math.round((item.discount_amount / originalTotal) * 100);
};

// Calculate totals
const totalQuantity = computed(() => props.items.reduce((sum, item) => sum + item.quantity, 0));
const totalOriginal = computed(() => props.items.reduce((sum, item) => sum + getOriginalTotal(item), 0));
const totalDiscount = computed(() => props.items.reduce((sum, item) => sum + (item.discount_amount || 0), 0));
const totalFinal = computed(() => props.items.reduce((sum, item) => sum + item.total_amount, 0));
</script>

<template>
    <div v-if="items && items.length > 0" class="space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <ShoppingCart class="h-4 w-4" />
                {{ __('Order Items') }}
            </div>
            <Badge variant="secondary" class="font-semibold">
                {{ items.length }} {{ items.length === 1 ? __('item') : __('items') }}
            </Badge>
        </div>

        <!-- Items List (scrollable when 3+ items) -->
        <div class="border rounded-lg divide-y overflow-hidden" :class="{ 'max-h-[400px] overflow-y-auto': items.length >= 3 }">
            <div
                v-for="item in items"
                :key="item.id"
                class="p-4 hover:bg-muted/30 transition-colors"
            >
                <div class="flex gap-4">
                    <!-- Product Image -->
                    <div class="shrink-0 w-16 h-16 bg-muted rounded-lg flex items-center justify-center overflow-hidden border">
                        <img
                            v-if="item.product?.images?.[0]"
                            :src="item.product.images[0]"
                            :alt="item.product_name"
                            class="w-full h-full object-cover"
                        />
                        <Package v-else class="h-8 w-8 text-muted-foreground" />
                    </div>

                    <!-- Product Details -->
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <!-- Product Name & SKU -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-sm leading-tight truncate">{{ item.product_name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span v-if="item.product_sku" class="text-xs text-muted-foreground font-mono">
                                        {{ item.product_sku }}
                                    </span>
                                    <Badge v-if="hasDiscount(item)" variant="destructive" class="text-[10px] px-1.5 py-0 h-4 gap-0.5">
                                        <Percent class="h-2.5 w-2.5" />
                                        {{ __('Discounted') }}
                                    </Badge>
                                </div>
                            </div>
                            <!-- Item Total (Quick View) -->
                            <div class="text-right shrink-0">
                                <p class="font-bold text-sm tabular-nums text-primary">{{ formatCurrency(item.total_amount) }}</p>
                                <Badge v-if="hasDiscount(item)" variant="outline" class="text-[9px] px-1 py-0 h-4 text-green-600 border-green-300 dark:border-green-700">
                                    {{ __('Saved') }} {{ formatCurrency(item.discount_amount) }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Price Calculation Breakdown -->
                        <div class="text-xs space-y-1 bg-muted/30 rounded-md p-2 mt-1">
                            <!-- Unit Price × Quantity = Subtotal -->
                            <div class="flex items-center justify-between text-muted-foreground">
                                <div class="flex items-center gap-1">
                                    <Tag class="h-3 w-3" />
                                    <span>{{ formatCurrency(item.unit_price) }}</span>
                                    <X class="h-2.5 w-2.5" />
                                    <span>{{ item.quantity }}</span>
                                </div>
                                <span class="tabular-nums">= {{ formatCurrency(getOriginalTotal(item)) }}</span>
                            </div>

                            <!-- Discount (if any) -->
                            <div v-if="hasDiscount(item)" class="flex items-center justify-between text-green-600 dark:text-green-400">
                                <div class="flex items-center gap-1">
                                    <Minus class="h-3 w-3" />
                                    <span>{{ __('Discount') }}</span>
                                    <Badge v-if="getDiscountPercentage(item)" variant="outline" class="text-[9px] px-1 py-0 h-3.5 text-green-600 border-green-300 dark:border-green-700">
                                        -{{ getDiscountPercentage(item) }}%
                                    </Badge>
                                </div>
                                <span class="tabular-nums">-{{ formatCurrency(item.discount_amount) }}</span>
                            </div>

                            <!-- Final Total (always show for clarity) -->
                            <div class="flex items-center justify-between font-medium text-foreground" :class="{ 'border-t border-dashed pt-1': hasDiscount(item) }">
                                <span>{{ __('Item Total') }}</span>
                                <span class="tabular-nums">{{ formatCurrency(item.total_amount) }}</span>
                            </div>
                        </div>

                        <!-- Item Notes -->
                        <div v-if="item.notes" class="mt-2">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted/50 px-2 py-1 rounded w-fit max-w-full">
                                            <Info class="h-3 w-3 shrink-0" />
                                            <span class="truncate">{{ item.notes }}</span>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p class="max-w-xs">{{ item.notes }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary with Calculation -->
        <div class="bg-muted/50 rounded-lg p-3 space-y-2 text-sm">
            <div class="flex items-center justify-between text-muted-foreground">
                <span>{{ __('Total Quantity') }}</span>
                <strong class="text-foreground tabular-nums">{{ totalQuantity }} {{ totalQuantity === 1 ? __('item') : __('items') }}</strong>
            </div>
            <div class="flex items-center justify-between text-muted-foreground">
                <span>{{ __('Original Total') }}</span>
                <span class="tabular-nums">{{ formatCurrency(totalOriginal) }}</span>
            </div>
            <div v-if="totalDiscount > 0" class="flex items-center justify-between text-green-600 dark:text-green-400">
                <div class="flex items-center gap-1">
                    <Minus class="h-3 w-3" />
                    <span>{{ __('Total Discount') }}</span>
                </div>
                <span class="tabular-nums">-{{ formatCurrency(totalDiscount) }}</span>
            </div>
            <div class="flex items-center justify-between font-semibold pt-2 border-t">
                <span>{{ __('Items Total') }}</span>
                <span class="tabular-nums text-primary">{{ formatCurrency(totalFinal) }}</span>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div v-else class="p-6 border rounded-lg border-dashed text-center">
        <ShoppingCart class="h-10 w-10 mx-auto mb-3 text-muted-foreground/40" />
        <p class="text-muted-foreground font-medium">{{ __('No items in this order') }}</p>
        <p class="text-xs text-muted-foreground/70 mt-1">{{ __('Items will appear here once added') }}</p>
    </div>
</template>
