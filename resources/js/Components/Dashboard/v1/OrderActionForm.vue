<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    CheckCircle,
    Clock,
    ChefHat,
    Package,
    Truck,
    PackageCheck,
    XCircle,
    RotateCcw,
    CreditCard,
    Hash,
    Calendar,
    DollarSign,
    Banknote,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import { toast } from 'vue-sonner';
import { ModalForm, ModalConfirm } from '@/components/shared';
import type { OrderActionData } from '@order/types';

// Import widgets
import {
    OrderProgressWidget,
    OrderItemsWidget,
    OrderShippingWidget,
    OrderCustomerWidget,
    OrderTotalsWidget,
} from './Widgets/OrderActionFormWidget';

const { __ } = useTranslation();

const props = defineProps<{
    open: boolean;
    order: OrderActionData | null;
    targetStatus?: string;
    loadingOrder?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    success: [];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

// State for confirmation modal
const isUpdating = ref(false);
const showConfirmModal = ref(false);
const pendingAction = ref<{
    status: string;
    title: string;
    description: string;
    confirmText: string;
    variant: 'default' | 'danger';
} | null>(null);

// Status workflow (matches OrderStatusEnum)
// Note: pending → preparing directly (auto-confirm on accept)
const statusFlow: Record<string, { next: string; label: string; icon: typeof Clock; description?: string } | null> = {
    pending: { next: 'preparing', label: 'Accept & Start Preparing', icon: ChefHat, description: 'Accept order and start preparing' },
    confirmed: { next: 'preparing', label: 'Start Preparing', icon: ChefHat },
    preparing: { next: 'ready', label: 'Mark as Ready', icon: Package, description: 'Order is ready for pickup/delivery' },
    ready: { next: 'delivering', label: 'Start Delivery', icon: Truck, description: 'Hand over to delivery' },
    delivering: { next: 'delivered', label: 'Mark as Delivered', icon: PackageCheck, description: 'Confirm customer received' },
    delivered: { next: 'completed', label: 'Complete Order', icon: CheckCircle, description: 'Finalize the order' },
    completed: null,
    cancelled: null,
    refunded: null,
};

// Status colors for visual indicator
const statusColors: Record<string, { bg: string; text: string; border: string }> = {
    pending: { bg: 'bg-yellow-50 dark:bg-yellow-950', text: 'text-yellow-700 dark:text-yellow-300', border: 'border-yellow-200 dark:border-yellow-800' },
    confirmed: { bg: 'bg-blue-50 dark:bg-blue-950', text: 'text-blue-700 dark:text-blue-300', border: 'border-blue-200 dark:border-blue-800' },
    preparing: { bg: 'bg-orange-50 dark:bg-orange-950', text: 'text-orange-700 dark:text-orange-300', border: 'border-orange-200 dark:border-orange-800' },
    ready: { bg: 'bg-purple-50 dark:bg-purple-950', text: 'text-purple-700 dark:text-purple-300', border: 'border-purple-200 dark:border-purple-800' },
    delivering: { bg: 'bg-indigo-50 dark:bg-indigo-950', text: 'text-indigo-700 dark:text-indigo-300', border: 'border-indigo-200 dark:border-indigo-800' },
    delivered: { bg: 'bg-teal-50 dark:bg-teal-950', text: 'text-teal-700 dark:text-teal-300', border: 'border-teal-200 dark:border-teal-800' },
    completed: { bg: 'bg-green-50 dark:bg-green-950', text: 'text-green-700 dark:text-green-300', border: 'border-green-200 dark:border-green-800' },
    cancelled: { bg: 'bg-red-50 dark:bg-red-950', text: 'text-red-700 dark:text-red-300', border: 'border-red-200 dark:border-red-800' },
    refunded: { bg: 'bg-gray-50 dark:bg-gray-950', text: 'text-gray-700 dark:text-gray-300', border: 'border-gray-200 dark:border-gray-800' },
};

const statusIcons: Record<string, typeof Clock> = {
    pending: Clock,
    confirmed: CheckCircle,
    preparing: ChefHat,
    ready: Package,
    delivering: Truck,
    delivered: PackageCheck,
    completed: CheckCircle,
    cancelled: XCircle,
    refunded: RotateCcw,
};

// Action modal info for each status transition
const getActionInfo = (status: string): { title: string; description: string; confirmText: string; variant: 'default' | 'danger' } => {
    const orderNumber = props.order?.order_number || '';
    const info: Record<string, { title: string; description: string; confirmText: string; variant: 'default' | 'danger' }> = {
        confirmed: {
            title: __('Confirm Order'),
            description: __('Accept order :order? The customer will be notified.', { order: orderNumber }),
            confirmText: __('Confirm Order'),
            variant: 'default',
        },
        preparing: {
            title: __('Start Preparing'),
            description: __('Start preparing order :order? The customer will be notified that their order is being prepared.', { order: orderNumber }),
            confirmText: __('Start Preparing'),
            variant: 'default',
        },
        ready: {
            title: __('Mark Order Ready'),
            description: __('Mark order :order as ready? The customer and delivery will be notified.', { order: orderNumber }),
            confirmText: __('Mark Ready'),
            variant: 'default',
        },
        delivering: {
            title: __('Start Delivery'),
            description: __('Start delivery for order :order? The customer will receive tracking updates.', { order: orderNumber }),
            confirmText: __('Start Delivery'),
            variant: 'default',
        },
        delivered: {
            title: __('Mark as Delivered'),
            description: __('Confirm that order :order has been delivered to the customer?', { order: orderNumber }),
            confirmText: __('Mark Delivered'),
            variant: 'default',
        },
        completed: {
            title: __('Complete Order'),
            description: __('Complete order :order? This will finalize the order and mark it as done.', { order: orderNumber }),
            confirmText: __('Complete Order'),
            variant: 'default',
        },
        cancelled: {
            title: __('Cancel Order'),
            description: __('Are you sure you want to cancel order :order? This action cannot be undone and the customer will be notified.', { order: orderNumber }),
            confirmText: __('Cancel Order'),
            variant: 'danger',
        },
        refunded: {
            title: __('Refund Order'),
            description: __('Process refund for order :order? The payment will be returned to the customer.', { order: orderNumber }),
            confirmText: __('Process Refund'),
            variant: 'danger',
        },
    };
    return info[status] || { title: __('Confirm Action'), description: '', confirmText: __('Confirm'), variant: 'default' };
};

const currentStatusAction = computed(() => {
    if (!props.order) return null;
    return statusFlow[props.order.status] || null;
});

const currentStatusColor = computed(() => {
    return statusColors[props.order?.status || 'pending'] || statusColors.pending;
});

const currentStatusIcon = computed(() => {
    return statusIcons[props.order?.status || 'pending'] || Clock;
});

// Cancel only available for pending orders (before confirmation)
const canCancel = computed(() => {
    return props.order && props.order.status === 'pending';
});

// Refund available for delivered/completed orders
const canRefund = computed(() => {
    return props.order && ['delivered', 'completed'].includes(props.order.status);
});

// Open confirmation modal with action info
const openConfirmModal = (status: string) => {
    const info = getActionInfo(status);
    pendingAction.value = { status, ...info };
    showConfirmModal.value = true;
};

// Handle next status button click
const handleNextStatus = () => {
    if (currentStatusAction.value) {
        openConfirmModal(currentStatusAction.value.next);
    }
};

// Handle cancel button click
const handleCancelClick = () => {
    openConfirmModal('cancelled');
};

// Handle refund button click
const handleRefundClick = () => {
    openConfirmModal('refunded');
};

// Confirm action from modal (use UUID for route)
const confirmAction = () => {
    if (!pendingAction.value || !props.order) return;

    isUpdating.value = true;
    router.put(`/dashboard/orders/${props.order.uuid}/status`, {
        status: pendingAction.value.status,
        from_modal: true,
    }, {
        onSuccess: () => {
            const newStatus = pendingAction.value?.status || '';
            toast.success(__('Order status updated to :status', { status: newStatus }));
            showConfirmModal.value = false;
            pendingAction.value = null;
            isOpen.value = false;
            emit('success');
        },
        onError: () => {
            toast.error(__('Failed to update order status'));
        },
        onFinish: () => {
            isUpdating.value = false;
        },
    });
};

const handleClose = () => {
    isOpen.value = false;
};

const getStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        pending: 'outline',
        confirmed: 'secondary',
        preparing: 'default',
        ready: 'default',
        delivering: 'default',
        delivered: 'secondary',
        completed: 'secondary',
        cancelled: 'destructive',
        refunded: 'outline',
    };
    return variants[status] || 'outline';
};

const getPaymentStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        pending: 'outline',
        paid: 'secondary',
        failed: 'destructive',
        refunded: 'outline',
        partial: 'default',
    };
    return variants[status] || 'outline';
};

// Format currency
const formatCurrency = (amount: number | undefined) => {
    if (amount === undefined) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

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
    <ModalForm
        v-model:open="isOpen"
        :title="__('Order Management')"
        :description="__('View and manage order details')"
        size="xl"
        :loading="isUpdating"
        @cancel="handleClose"
    >
        <!-- Loading State -->
        <div v-if="props.loadingOrder" class="space-y-4 animate-pulse">
            <div class="h-24 bg-muted rounded-xl" />
            <div class="h-16 bg-muted rounded-lg" />
            <div class="grid grid-cols-2 gap-4">
                <div class="h-28 bg-muted rounded-lg" />
                <div class="h-28 bg-muted rounded-lg" />
            </div>
            <div class="h-40 bg-muted rounded-lg" />
        </div>

        <div v-else-if="order" class="space-y-6">
            <!-- Order Header Card -->
            <Card :class="['border-2', currentStatusColor.border, currentStatusColor.bg]">
                <CardContent class="p-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <!-- Order Info -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Hash class="h-5 w-5 text-muted-foreground" />
                                <span class="text-2xl font-bold tracking-tight">{{ order.order_number }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                <div class="flex items-center gap-1.5">
                                    <Calendar class="h-4 w-4" />
                                    {{ formatDate(order.created_at) }}
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <DollarSign class="h-4 w-4" />
                                    <span class="font-semibold text-foreground">{{ formatCurrency(order.total_amount) }}</span>
                                </div>
                                <div v-if="order.items_count" class="flex items-center gap-1.5">
                                    <Package class="h-4 w-4" />
                                    {{ order.items_count }} {{ order.items_count === 1 ? 'item' : 'items' }}
                                </div>
                            </div>
                        </div>

                        <!-- Status & Payment Badges -->
                        <div class="flex flex-col items-end gap-2">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" :class="currentStatusColor.bg">
                                <component :is="currentStatusIcon" class="h-5 w-5" :class="currentStatusColor.text" />
                                <span class="font-semibold capitalize" :class="currentStatusColor.text">{{ order.status }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="getPaymentStatusVariant(order.payment_status)" class="capitalize gap-1">
                                    <Banknote class="h-3 w-3" />
                                    {{ order.payment_status }}
                                </Badge>
                                <Badge v-if="order.payment_method" variant="outline" class="capitalize gap-1">
                                    <CreditCard class="h-3 w-3" />
                                    {{ order.payment_method.replace('_', ' ') }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Progress Tracker -->
            <OrderProgressWidget :status="order.status" />

            <!-- Quick Action Card -->
            <Card v-if="currentStatusAction || canCancel || canRefund" class="border-primary/20 bg-primary/5">
                <CardContent class="p-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-primary">{{ __('Next Action') }}</p>
                            <p v-if="currentStatusAction" class="text-sm text-muted-foreground">
                                {{ currentStatusAction.description || __('Update the order status') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <!-- Next Status Button -->
                            <Button
                                v-if="currentStatusAction"
                                :disabled="isUpdating"
                                @click="handleNextStatus"
                                class="gap-2"
                                size="lg"
                            >
                                <component :is="currentStatusAction.icon" class="h-5 w-5" />
                                {{ currentStatusAction.label }}
                            </Button>

                            <!-- Cancel Button -->
                            <Button
                                v-if="canCancel"
                                variant="destructive"
                                :disabled="isUpdating"
                                @click="handleCancelClick"
                                class="gap-2"
                            >
                                <XCircle class="h-4 w-4" />
                                {{ __('Cancel Order') }}
                            </Button>

                            <!-- Refund Button -->
                            <Button
                                v-if="canRefund"
                                variant="outline"
                                :disabled="isUpdating"
                                @click="handleRefundClick"
                                class="gap-2 text-orange-600 border-orange-300 hover:bg-orange-50 dark:hover:bg-orange-950"
                            >
                                <RotateCcw class="h-4 w-4" />
                                {{ __('Refund Order') }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Completed/Cancelled/Refunded Status Message -->
            <Card v-if="['completed', 'cancelled', 'refunded'].includes(order.status)" :class="[
                'border-2',
                order.status === 'completed' ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' :
                order.status === 'cancelled' ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950' :
                'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950'
            ]">
                <CardContent class="p-4">
                    <div class="flex items-center gap-3">
                        <div :class="[
                            'flex items-center justify-center w-12 h-12 rounded-full',
                            order.status === 'completed' ? 'bg-green-100 dark:bg-green-900' :
                            order.status === 'cancelled' ? 'bg-red-100 dark:bg-red-900' :
                            'bg-gray-100 dark:bg-gray-900'
                        ]">
                            <CheckCircle v-if="order.status === 'completed'" class="h-6 w-6 text-green-600 dark:text-green-400" />
                            <XCircle v-else-if="order.status === 'cancelled'" class="h-6 w-6 text-red-600 dark:text-red-400" />
                            <RotateCcw v-else class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                        </div>
                        <div>
                            <p :class="[
                                'font-semibold text-lg',
                                order.status === 'completed' ? 'text-green-700 dark:text-green-300' :
                                order.status === 'cancelled' ? 'text-red-700 dark:text-red-300' :
                                'text-gray-700 dark:text-gray-300'
                            ]">
                                {{ order.status === 'completed' ? __('Order Completed Successfully!') :
                                   order.status === 'cancelled' ? __('Order Has Been Cancelled') :
                                   __('Order Has Been Refunded') }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ order.status === 'completed' ? __('Thank you for using our service.') :
                                   order.status === 'cancelled' ? __('This order was cancelled and cannot be modified.') :
                                   __('The payment has been returned to the customer.') }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Separator />

            <!-- Two Column Layout for Customer & Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <!-- Left Column: Customer & Outlet -->
                <OrderCustomerWidget
                    :customer="order.customer"
                    :outlet="order.outlet"
                    :notes="order.notes"
                    :created-at="order.created_at"
                    :shipped-at="order.shipped_at"
                    :delivered-at="order.delivered_at"
                    :cancelled-at="order.cancelled_at"
                    :completed-at="order.completed_at"
                />

                <!-- Right Column: Order Items + Summary -->
                <Card class="h-fit">
                    <CardContent class="p-4 space-y-4">
                        <!-- Order Items -->
                        <OrderItemsWidget :items="order.items || []" />

                        <!-- Order Summary (inside items card) -->
                        <OrderTotalsWidget
                            :subtotal="order.subtotal"
                            :discount-amount="order.discount_amount"
                            :tax-amount="order.tax_amount"
                            :shipping-cost="order.shipping?.shipping_cost"
                            :total-amount="order.total_amount"
                            :items-count="order.items?.length"
                        />
                    </CardContent>
                </Card>
            </div>

            <!-- Shipping Route - Full Width (FROM outlet TO customer) -->
            <OrderShippingWidget :shipping="order.shipping" :outlet="order.outlet" />
        </div>

        <!-- No Order State -->
        <div v-else class="text-center py-12">
            <Package class="h-12 w-12 mx-auto text-muted-foreground/50 mb-4" />
            <p class="text-muted-foreground">{{ __('Order not found') }}</p>
        </div>

        <template #footer>
            <div class="flex w-full gap-2">
                <Button
                    type="button"
                    variant="outline"
                    class="flex-1"
                    @click="handleClose"
                >
                    {{ __('Close') }}
                </Button>
            </div>
        </template>
    </ModalForm>

    <!-- Confirmation Modal for status changes -->
    <ModalConfirm
        v-model:open="showConfirmModal"
        :title="pendingAction?.title || __('Confirm Action')"
        :description="pendingAction?.description || __('Are you sure?')"
        :confirm-text="pendingAction?.confirmText || __('Confirm')"
        :variant="pendingAction?.variant || 'default'"
        :loading="isUpdating"
        @confirm="confirmAction"
    />
</template>
