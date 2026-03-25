<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, type VNode } from 'vue';
import {
    ArrowLeft,
    Pencil,
    Package,
    User,
    MapPin,
    Calendar,
    Truck,
    CheckCircle,
    Clock,
    XCircle,
    ChefHat,
    PackageCheck,
    RotateCcw,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { toast } from '@/composables/useToast';
import { ModalConfirm } from '@/components/shared';
import type { OrderItem } from '@order/types';

defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Orders', href: '/dashboard/orders' },
            { title: 'Order Details', href: '#' },
        ]}, () => page),
});

const props = defineProps<{
    order: OrderItem;
}>();

// State - ONE modal for all actions
const isUpdating = ref(false);
const showActionModal = ref(false);
const pendingAction = ref<{
    status: string;
    title: string;
    description: string;
    confirmText: string;
    variant: 'default' | 'danger';
} | null>(null);

// Status workflow (matches OrderStatusEnum)
// Pending → Confirmed → Preparing → Ready → Delivering → Delivered → Completed
const statusFlow: Record<string, { next: string; label: string; icon: typeof Clock } | null> = {
    pending: { next: 'confirmed', label: 'Confirm Order', icon: CheckCircle },
    confirmed: { next: 'preparing', label: 'Start Preparing', icon: ChefHat },
    preparing: { next: 'ready', label: 'Mark Ready', icon: Package },
    ready: { next: 'delivering', label: 'Start Delivery', icon: Truck },
    delivering: { next: 'delivered', label: 'Mark Delivered', icon: PackageCheck },
    delivered: { next: 'completed', label: 'Complete Order', icon: CheckCircle },
    completed: null,
    cancelled: null,
    refunded: null,
};

// Action modal info for each status transition
const actionInfo: Record<string, { title: string; description: string; confirmText: string; variant: 'default' | 'danger' }> = {
    confirmed: {
        title: 'Confirm Order',
        description: `Accept order ${props.order.order_number}? The customer will be notified.`,
        confirmText: 'Confirm Order',
        variant: 'default',
    },
    preparing: {
        title: 'Start Preparing',
        description: `Start preparing order ${props.order.order_number}? The customer will be notified.`,
        confirmText: 'Start Preparing',
        variant: 'default',
    },
    ready: {
        title: 'Mark Order Ready',
        description: `Mark order ${props.order.order_number} as ready for pickup/delivery?`,
        confirmText: 'Mark Ready',
        variant: 'default',
    },
    delivering: {
        title: 'Start Delivery',
        description: `Start delivery for order ${props.order.order_number}? The customer will be notified.`,
        confirmText: 'Start Delivery',
        variant: 'default',
    },
    delivered: {
        title: 'Mark as Delivered',
        description: `Confirm that order ${props.order.order_number} has been delivered to the customer?`,
        confirmText: 'Mark Delivered',
        variant: 'default',
    },
    completed: {
        title: 'Complete Order',
        description: `Complete order ${props.order.order_number}? This will finalize the order.`,
        confirmText: 'Complete Order',
        variant: 'default',
    },
    cancelled: {
        title: 'Cancel Order',
        description: `Are you sure you want to cancel order ${props.order.order_number}? This action cannot be undone.`,
        confirmText: 'Cancel Order',
        variant: 'danger',
    },
    refunded: {
        title: 'Refund Order',
        description: `Process refund for order ${props.order.order_number}? The customer will be notified.`,
        confirmText: 'Process Refund',
        variant: 'danger',
    },
};

const currentStatusAction = computed(() => {
    return statusFlow[props.order.status] || null;
});

// Cancel available until delivered (matches OrderStatusEnum.canBeCancelled())
const canCancel = computed(() => {
    return ['pending', 'confirmed', 'preparing', 'ready', 'delivering'].includes(props.order.status);
});

// Refund available for delivered/completed orders
const canRefund = computed(() => {
    return ['delivered', 'completed'].includes(props.order.status);
});

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
    };
    return variants[status] || 'outline';
};

const getStatusIcon = (status: string) => {
    const icons: Record<string, typeof Clock> = {
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
    return icons[status] || Clock;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (date: string | null) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleEdit = () => {
    router.visit(`/dashboard/orders/${props.order.uuid}/edit`);
};

const handleBack = () => {
    router.visit('/dashboard/orders');
};

// Open modal with action info
const openActionModal = (status: string) => {
    const info = actionInfo[status];
    if (info) {
        pendingAction.value = { status, ...info };
        showActionModal.value = true;
    }
};

// Handle next status button click
const handleNextStatus = () => {
    if (currentStatusAction.value) {
        openActionModal(currentStatusAction.value.next);
    }
};

// Handle cancel button click
const handleCancelClick = () => {
    openActionModal('cancelled');
};

// Handle refund button click
const handleRefundClick = () => {
    openActionModal('refunded');
};

// Confirm action from modal (use UUID for route)
const confirmAction = () => {
    if (!pendingAction.value) return;

    isUpdating.value = true;
    router.put(`/dashboard/orders/${props.order.uuid}/status`, {
        status: pendingAction.value.status,
    }, {
        onSuccess: () => {
            toast.success(`Order status updated to ${pendingAction.value?.status}`);
            showActionModal.value = false;
            pendingAction.value = null;
        },
        onError: () => {
            toast.error('Failed to update order status');
        },
        onFinish: () => {
            isUpdating.value = false;
        },
    });
};

const handleMarkPaid = () => {
    isUpdating.value = true;
    router.put(`/dashboard/orders/${props.order.uuid}/payment-status`, {
        payment_status: 'paid',
    }, {
        onSuccess: () => {
            toast.success('Payment marked as paid');
        },
        onFinish: () => {
            isUpdating.value = false;
        },
    });
};

// Helper function for progress bar (7-step flow)
const getProgressPercent = (status: string): number => {
    const progress: Record<string, number> = {
        pending: 0,
        confirmed: 15,
        preparing: 30,
        ready: 45,
        delivering: 60,
        delivered: 80,
        completed: 100,
        cancelled: 0,
        refunded: 100,
    };
    return progress[status] || 0;
};
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="handleBack">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ order.order_number }}</h1>
                    <p class="text-muted-foreground">Order details and management</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" @click="handleEdit">
                    <Pencil class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Action Buttons Card -->
                <Card class="border-2 border-primary/20">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <component :is="getStatusIcon(order.status)" class="h-5 w-5" />
                            Order Actions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Current Status -->
                        <div class="mb-4 flex items-center gap-3">
                            <span class="text-sm text-muted-foreground">Current Status:</span>
                            <Badge :variant="getStatusVariant(order.status)" class="capitalize text-sm">
                                {{ order.status }}
                            </Badge>
                            <Badge :variant="getPaymentStatusVariant(order.payment_status)" class="capitalize text-sm">
                                Payment: {{ order.payment_status }}
                            </Badge>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3">
                            <!-- Next Status Button -->
                            <Button
                                v-if="currentStatusAction"
                                :disabled="isUpdating"
                                @click="handleNextStatus"
                                class="gap-2"
                            >
                                <component :is="currentStatusAction.icon" class="h-4 w-4" />
                                {{ currentStatusAction.label }}
                            </Button>

                            <!-- Mark as Paid (if payment pending) -->
                            <Button
                                v-if="order.payment_status === 'pending'"
                                variant="outline"
                                :disabled="isUpdating"
                                @click="handleMarkPaid"
                                class="gap-2"
                            >
                                <CheckCircle class="h-4 w-4" />
                                Mark as Paid
                            </Button>

                            <!-- Cancel Button - ONLY for pending orders -->
                            <Button
                                v-if="canCancel"
                                variant="destructive"
                                :disabled="isUpdating"
                                @click="handleCancelClick"
                                class="gap-2"
                            >
                                <XCircle class="h-4 w-4" />
                                Cancel Order
                            </Button>

                            <!-- Refund Button - for delivered/completed -->
                            <Button
                                v-if="canRefund"
                                variant="outline"
                                :disabled="isUpdating"
                                @click="handleRefundClick"
                                class="gap-2 text-orange-600 border-orange-300 hover:bg-orange-50"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Refund Order
                            </Button>

                            <!-- Completed message -->
                            <div v-if="order.status === 'completed'" class="flex items-center gap-2 text-green-600">
                                <CheckCircle class="h-5 w-5" />
                                <span class="font-medium">Order Completed!</span>
                            </div>

                            <!-- Cancelled message -->
                            <div v-if="order.status === 'cancelled'" class="flex items-center gap-2 text-red-600">
                                <XCircle class="h-5 w-5" />
                                <span class="font-medium">Order Cancelled</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-6">
                            <div class="flex justify-between text-xs text-muted-foreground mb-2">
                                <span>Pending</span>
                                <span>Confirmed</span>
                                <span>Preparing</span>
                                <span>Ready</span>
                                <span>Delivering</span>
                                <span>Delivered</span>
                                <span>Completed</span>
                            </div>
                            <div class="h-2 bg-muted rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-primary transition-all duration-500"
                                    :style="{ width: getProgressPercent(order.status) + '%' }"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Order Items -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Order Items ({{ order.items?.length || 0 }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-center justify-between rounded-lg border p-4"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-muted">
                                        <Package class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ item.product_name }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            SKU: {{ item.product_sku || 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">{{ formatCurrency(item.total_amount) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!order.items?.length" class="py-8 text-center text-muted-foreground">
                                No items in this order.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card v-if="order.notes">
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-wrap text-muted-foreground">{{ order.notes }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Order Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>Order Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span>{{ formatCurrency(order.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Discount</span>
                            <span class="text-red-500">-{{ formatCurrency(order.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Tax</span>
                            <span>{{ formatCurrency(order.tax_amount) }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span>{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Customer Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Customer
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="order.customer">
                            <p class="font-medium">{{ order.customer.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ order.customer.email }}</p>
                            <p v-if="order.customer.phone" class="text-sm text-muted-foreground">
                                {{ order.customer.phone }}
                            </p>
                        </div>
                        <p v-else class="text-muted-foreground">Guest Customer</p>
                    </CardContent>
                </Card>

                <!-- Outlet Info -->
                <Card v-if="order.outlet">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Outlet
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-medium">{{ order.outlet.name }}</p>
                    </CardContent>
                </Card>

                <!-- Shipping Info -->
                <Card v-if="order.shipping">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Truck class="h-5 w-5" />
                            Shipping
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="order.shipping.recipient_name">
                            <p class="text-sm text-muted-foreground">Recipient</p>
                            <p class="text-sm font-medium">{{ order.shipping.recipient_name }}</p>
                            <p v-if="order.shipping.phone" class="text-sm text-muted-foreground">{{ order.shipping.phone }}</p>
                        </div>
                        <div v-if="order.shipping.street_1">
                            <p class="text-sm text-muted-foreground">Address</p>
                            <p class="text-sm font-medium">
                                {{ order.shipping.street_1 }}
                                <span v-if="order.shipping.street_2">, {{ order.shipping.street_2 }}</span>
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ [order.shipping.city, order.shipping.state, order.shipping.postal_code].filter(Boolean).join(', ') }}
                            </p>
                            <p v-if="order.shipping.country" class="text-sm text-muted-foreground">{{ order.shipping.country }}</p>
                        </div>
                        <div v-if="order.shipping.carrier || order.shipping.method">
                            <p class="text-sm text-muted-foreground">Carrier</p>
                            <p class="text-sm font-medium">
                                {{ order.shipping.carrier || 'N/A' }}
                                <span v-if="order.shipping.method"> - {{ order.shipping.method }}</span>
                            </p>
                        </div>
                        <div v-if="order.shipping.tracking_number">
                            <p class="text-sm text-muted-foreground">Tracking</p>
                            <p class="text-sm font-medium font-mono">{{ order.shipping.tracking_number }}</p>
                        </div>
                        <div v-if="order.shipping.shipping_cost">
                            <p class="text-sm text-muted-foreground">Shipping Cost</p>
                            <p class="text-sm font-medium">{{ formatCurrency(order.shipping.shipping_cost) }}</p>
                        </div>
                        <div v-if="order.shipping.latitude && order.shipping.longitude" class="pt-2">
                            <p class="text-sm text-muted-foreground">GPS Coordinates</p>
                            <p class="text-sm font-mono">{{ order.shipping.latitude }}, {{ order.shipping.longitude }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Timestamps -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Timeline
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Created</p>
                            <p class="text-sm font-medium">{{ formatDate(order.created_at) }}</p>
                        </div>
                        <div v-if="order.shipped_at">
                            <p class="text-sm text-muted-foreground">Delivering</p>
                            <p class="text-sm font-medium">{{ formatDate(order.shipped_at) }}</p>
                        </div>
                        <div v-if="order.delivered_at">
                            <p class="text-sm text-muted-foreground">Delivered</p>
                            <p class="text-sm font-medium">{{ formatDate(order.delivered_at) }}</p>
                        </div>
                        <div v-if="order.cancelled_at">
                            <p class="text-sm text-muted-foreground">Cancelled</p>
                            <p class="text-sm font-medium text-red-500">{{ formatDate(order.cancelled_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <!-- Single Action Modal for ALL status changes -->
    <ModalConfirm
        v-model:open="showActionModal"
        :title="pendingAction?.title || 'Confirm Action'"
        :description="pendingAction?.description || 'Are you sure?'"
        :confirm-text="pendingAction?.confirmText || 'Confirm'"
        :variant="pendingAction?.variant || 'default'"
        :loading="isUpdating"
        @confirm="confirmAction"
    />
</template>
