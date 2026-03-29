<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, type VNode } from 'vue';
import {
    Plus,
    Package,
    Clock,
    CheckCircle,
    Truck,
    X,
    DollarSign,
    RefreshCw,
    ChefHat,
    PackageCheck,
    XCircle,
    Search,
    Eye,
    Pencil,
    Trash2,
    User,
    Store,
    Calendar,
    Hash,
    CreditCard,
    Banknote,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { toast } from '@/composables/useToast';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { ModalConfirm, StatsCard, Pagination, CardWidget, SidebarFilter, type CardAction, type FilterItem } from '@/components/shared';
import { formatRelativeTime, isRecentDate } from '@/composables/useRelativeTime';
import type { OrderIndexProps, OrderItem } from '@order/types';

// Persistent layout required for momentum-modal
defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Orders', href: '/dashboard/orders' },
        ]}, () => page),
});

const props = defineProps<OrderIndexProps>();

// State
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const paymentStatusFilter = ref(props.filters.payment_status || 'all');
const outletFilter = ref(props.filters.outlet_id?.toString() || 'all');
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);
const selectedItem = ref<OrderItem | null>(null);

// Status tabs configuration
const statusTabs = computed<FilterItem[]>(() => [
    { key: 'all', label: 'All', count: props.stats.total, icon: Package, color: 'text-blue-600', bgColor: 'bg-blue-100' },
    { key: 'pending', label: 'Pending', count: props.stats.pending ?? 0, icon: Clock, color: 'text-yellow-600', bgColor: 'bg-yellow-100' },
    { key: 'confirmed', label: 'Confirmed', count: props.stats.confirmed ?? 0, icon: CheckCircle, color: 'text-sky-600', bgColor: 'bg-sky-100' },
    { key: 'preparing', label: 'Preparing', count: props.stats.preparing ?? 0, icon: ChefHat, color: 'text-orange-600', bgColor: 'bg-orange-100' },
    { key: 'ready', label: 'Ready', count: props.stats.ready ?? 0, icon: Package, color: 'text-violet-600', bgColor: 'bg-violet-100' },
    { key: 'delivering', label: 'Delivering', count: props.stats.delivering ?? 0, icon: Truck, color: 'text-indigo-600', bgColor: 'bg-indigo-100' },
    { key: 'delivered', label: 'Delivered', count: props.stats.delivered ?? 0, icon: PackageCheck, color: 'text-teal-600', bgColor: 'bg-teal-100' },
    { key: 'completed', label: 'Completed', count: props.stats.completed ?? 0, icon: CheckCircle, color: 'text-emerald-600', bgColor: 'bg-emerald-100' },
    { key: 'cancelled', label: 'Cancelled', count: props.stats.cancelled ?? 0, icon: XCircle, color: 'text-red-600', bgColor: 'bg-red-100' },
    { key: 'refunded', label: 'Refunded', count: props.stats.refunded ?? 0, icon: RefreshCw, color: 'text-gray-600', bgColor: 'bg-gray-100' },
]);

// Handle status tab click - use the passed value directly for immediate effect
const handleStatusTabClick = (status: string) => {
    router.get('/dashboard/orders', {
        search: searchQuery.value || undefined,
        status: status !== 'all' ? status : undefined,
        payment_status: paymentStatusFilter.value !== 'all' ? paymentStatusFilter.value : undefined,
        outlet_id: outletFilter.value !== 'all' ? outletFilter.value : undefined,
        page: 1,
    }, { preserveState: true, preserveScroll: true });
};

// Pagination computed
const pagination = computed(() => ({
    current_page: props.orderItems.meta.current_page,
    last_page: props.orderItems.meta.last_page,
    per_page: props.orderItems.meta.per_page,
    total: props.orderItems.meta.total,
}));

// Card actions handlers
const handleView = (item: OrderItem) => router.visit(`/dashboard/orders/${item.uuid}`);
const handleUpdateStatus = (item: OrderItem) => router.visit(`/dashboard/orders/${item.uuid}/status`);
const handleEdit = (item: OrderItem) => router.visit(`/dashboard/orders/${item.uuid}/edit`);
const handleDeleteClick = (item: OrderItem) => {
    selectedItem.value = item;
    isDeleteModalOpen.value = true;
};

// Handlers
const handleCreate = () => router.visit('/dashboard/orders/create');

const applyFilters = (overrides: { page?: number; per_page?: number } = {}) => {
    router.get('/dashboard/orders', {
        search: searchQuery.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        payment_status: paymentStatusFilter.value !== 'all' ? paymentStatusFilter.value : undefined,
        outlet_id: outletFilter.value !== 'all' ? outletFilter.value : undefined,
        ...overrides,
    }, { preserveState: true });
};

const handlePageChange = (page: number) => {
    applyFilters({ page, per_page: pagination.value.per_page });
};

const handlePerPageChange = (perPage: number) => {
    applyFilters({ page: 1, per_page: perPage });
};

const handleSearch = (search: string) => {
    searchQuery.value = search;
    applyFilters({ page: 1 });
};

const handlePaymentStatusFilter = (value: string | number | boolean | bigint | Record<string, unknown> | null | undefined) => {
    paymentStatusFilter.value = String(value || 'all');
    applyFilters({ page: 1 });
};

const handleOutletFilter = (value: string | number | boolean | bigint | Record<string, unknown> | null | undefined) => {
    outletFilter.value = String(value || 'all');
    applyFilters({ page: 1 });
};

const handleDelete = () => {
    if (!selectedItem.value) return;
    isDeleting.value = true;
    router.delete(`/dashboard/orders/${selectedItem.value.uuid}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            selectedItem.value = null;
            toast.success('Order deleted successfully.');
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const handleRowClick = (item: OrderItem) => {
    router.visit(`/dashboard/orders/${item.uuid}`);
};

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return !!(searchQuery.value || statusFilter.value !== 'all' || paymentStatusFilter.value !== 'all' || outletFilter.value !== 'all');
});

const handleClearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    paymentStatusFilter.value = 'all';
    outletFilter.value = 'all';
    router.get('/dashboard/orders', {}, { preserveState: true, preserveScroll: true });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Format full date with time for tooltip
const formatFullDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

// Status badge config with professional colored backgrounds
const statusConfig: Record<string, { icon: typeof Clock; bgClass: string }> = {
    pending: { icon: Clock, bgClass: 'bg-yellow-500 text-white' },
    confirmed: { icon: CheckCircle, bgClass: 'bg-sky-500 text-white' },
    preparing: { icon: ChefHat, bgClass: 'bg-orange-500 text-white' },
    ready: { icon: PackageCheck, bgClass: 'bg-violet-500 text-white' },
    delivering: { icon: Truck, bgClass: 'bg-lime-500 text-white' },
    delivered: { icon: PackageCheck, bgClass: 'bg-teal-500 text-white' },
    completed: { icon: CheckCircle, bgClass: 'bg-emerald-500 text-white' },
    cancelled: { icon: XCircle, bgClass: 'bg-red-500 text-white' },
    refunded: { icon: RefreshCw, bgClass: 'bg-gray-500 text-white' },
};

const getStatusBgClass = (status: string) => statusConfig[status]?.bgClass || 'bg-gray-500 text-white';
const getStatusIcon = (status: string) => statusConfig[status]?.icon || Clock;

const paymentStatusConfig: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    pending: { variant: 'outline' },
    paid: { variant: 'secondary' },
    failed: { variant: 'destructive' },
    refunded: { variant: 'outline' },
    partial: { variant: 'default' },
};

const getPaymentStatusVariant = (status: string) => paymentStatusConfig[status]?.variant || 'outline';

const getPaymentMethodIcon = (method: string | null) => {
    if (!method) return null;
    if (method.includes('card') || method.includes('credit')) return CreditCard;
    if (method.includes('cash')) return Banknote;
    return DollarSign;
};

// Build card actions for each order
const getCardActions = (order: OrderItem): CardAction[] => [
    { label: 'View Details', icon: Eye, onClick: () => handleView(order) },
    { label: 'Update Status', icon: RefreshCw, onClick: () => handleUpdateStatus(order) },
    { label: 'Edit Order', icon: Pencil, onClick: () => handleEdit(order), separator: true },
    { label: 'Delete', icon: Trash2, onClick: () => handleDeleteClick(order), variant: 'destructive' },
];
</script>

<template>
    <Head title="Orders" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Orders</h1>
                <p class="text-muted-foreground">Manage and track customer orders</p>
            </div>
            <Button @click="handleCreate">
                <Plus class="mr-2 h-4 w-4" />
                New Order
            </Button>
        </div>

        <!-- Stats Cards Row -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatsCard
                title="Total Orders"
                :value="stats.total"
                :icon="Package"
                icon-color="text-blue-500"
            />
            <StatsCard
                title="In Progress"
                :value="(stats.pending ?? 0) + (stats.confirmed ?? 0) + (stats.preparing ?? 0) + (stats.ready ?? 0) + (stats.delivering ?? 0)"
                :icon="ChefHat"
                icon-color="text-orange-500"
                value-color="text-orange-600"
            />
            <StatsCard
                title="Completed"
                :value="stats.completed ?? 0"
                :icon="CheckCircle"
                icon-color="text-green-500"
                value-color="text-green-600"
            />
            <StatsCard
                title="Total Revenue"
                :value="formatCurrency(stats.total_revenue)"
                :icon="DollarSign"
                icon-color="text-emerald-500"
                value-color="text-emerald-600"
            />
        </div>

        <!-- Main Content: Left Status Sidebar + Right Order Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6 items-start">
            <!-- Left Sidebar: Status Filters (Sticky & Scrollable) -->
            <SidebarFilter
                v-model="statusFilter"
                title="Filter by Status"
                :items="statusTabs"
                @update:model-value="handleStatusTabClick"
            />

            <!-- Right Content: Search, Filters & Order Cards -->
            <div class="space-y-4">
                <!-- Search & Filters Bar -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search by order # or customer..."
                            class="pl-9"
                            @input="handleSearch(searchQuery)"
                        />
                    </div>

                    <!-- Payment Status Filter -->
                    <Select :model-value="paymentStatusFilter" @update:model-value="handlePaymentStatusFilter">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="All Payment" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Payment</SelectItem>
                            <SelectItem
                                v-for="status in props.paymentStatuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Outlet Filter -->
                    <Select v-if="props.outlets && props.outlets.length > 0" :model-value="outletFilter" @update:model-value="handleOutletFilter">
                        <SelectTrigger class="w-[160px]">
                            <SelectValue placeholder="All Outlets" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Outlets</SelectItem>
                            <SelectItem
                                v-for="outlet in props.outlets"
                                :key="outlet.id"
                                :value="outlet.id.toString()"
                            >
                                {{ outlet.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Clear Filters Button -->
                    <Button
                        v-if="hasActiveFilters"
                        variant="ghost"
                        size="sm"
                        @click="handleClearFilters"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <X class="mr-1 h-4 w-4" />
                        Clear
                    </Button>
                </div>

                <!-- Order Cards Grid -->
                <div v-if="props.orderItems.data.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <CardWidget
                        v-for="order in props.orderItems.data"
                        :key="order.id"
                        :actions="getCardActions(order)"
                        @click="handleRowClick(order)"
                    >
                        <!-- Header Icon -->
                        <template #header-icon>
                            <Hash class="h-4 w-4 text-muted-foreground" />
                        </template>

                        <!-- Header Title -->
                        <template #header-title>
                            <span class="font-mono font-bold text-primary">{{ order.order_number }}</span>
                        </template>

                        <!-- Header Badge (Status) -->
                        <template #header-badge>
                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold capitalize', getStatusBgClass(order.status)]">
                                <component :is="getStatusIcon(order.status)" class="h-3 w-3" />
                                {{ order.status }}
                            </span>
                        </template>

                        <!-- Body Content -->
                        <template #body>
                            <div class="flex items-center gap-2 text-sm">
                                <User class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span v-if="order.customer" class="truncate font-medium">{{ order.customer.name }}</span>
                                <span v-else class="text-muted-foreground italic">Guest</span>
                            </div>
                            <div v-if="order.outlet" class="flex items-center gap-2 text-sm">
                                <Store class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="truncate text-muted-foreground">{{ order.outlet.name }}</span>
                            </div>
                        </template>

                        <!-- Footer Left: Amount + Items -->
                        <template #footer-left>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-lg tabular-nums">{{ formatCurrency(order.total_amount ?? 0) }}</span>
                                <Badge variant="outline" class="text-xs tabular-nums">
                                    <Package class="h-3 w-3 mr-1" />
                                    {{ order.items_count ?? 0 }}
                                </Badge>
                            </div>
                        </template>

                        <!-- Footer Right: Payment Status -->
                        <template #footer-right>
                            <Badge :variant="getPaymentStatusVariant(order.payment_status)" class="capitalize text-xs">
                                {{ order.payment_status }}
                            </Badge>
                        </template>

                        <!-- Sub-footer: Date & Payment Method -->
                        <template #sub-footer>
                            <div class="flex items-center gap-1.5 mt-2 text-xs text-muted-foreground">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <span class="inline-flex items-center gap-1.5 cursor-default" :class="isRecentDate(order.created_at, 60) && 'text-green-600 dark:text-green-400 font-medium'">
                                                <Clock v-if="isRecentDate(order.created_at, 60)" class="h-3 w-3 text-green-500" />
                                                <Calendar v-else class="h-3 w-3" />
                                                {{ formatRelativeTime(order.created_at) }}
                                            </span>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{ formatFullDateTime(order.created_at) }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span v-if="order.payment_method" class="ml-auto flex items-center gap-1 capitalize">
                                    <component :is="getPaymentMethodIcon(order.payment_method)" class="h-3 w-3" />
                                    {{ order.payment_method.replace('_', ' ') }}
                                </span>
                            </div>
                        </template>
                    </CardWidget>
                </div>

                <!-- Empty State -->
                <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                    <Package class="h-16 w-16 text-muted-foreground/30 mb-4" />
                    <h3 class="text-lg font-medium mb-1">No orders found</h3>
                    <p class="text-muted-foreground text-sm mb-4">
                        {{ hasActiveFilters ? 'Try adjusting your filters' : 'Create your first order to get started' }}
                    </p>
                    <Button v-if="hasActiveFilters" variant="outline" @click="handleClearFilters">
                        <X class="mr-2 h-4 w-4" />
                        Clear Filters
                    </Button>
                    <Button v-else @click="handleCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        New Order
                    </Button>
                </div>

                <!-- Pagination -->
                <div v-if="props.orderItems.data.length > 0" class="flex items-center justify-between border-t pt-4">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
                        {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
                        {{ pagination.total }} orders
                    </p>
                    <div class="flex items-center gap-4">
                        <Select :model-value="pagination.per_page.toString()" @update:model-value="(v) => handlePerPageChange(Number(v))">
                            <SelectTrigger class="w-[100px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10 / page</SelectItem>
                                <SelectItem value="20">20 / page</SelectItem>
                                <SelectItem value="50">50 / page</SelectItem>
                            </SelectContent>
                        </Select>
                        <Pagination
                            :current-page="pagination.current_page"
                            :last-page="pagination.last_page"
                            @page-change="handlePageChange"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ModalConfirm
        v-model:open="isDeleteModalOpen"
        title="Delete Order"
        :description="`Are you sure you want to delete order ${selectedItem?.order_number}? This action cannot be undone and all associated data will be permanently removed.`"
        confirm-text="Delete Order"
        variant="danger"
        :loading="isDeleting"
        @confirm="handleDelete"
    />
</template>
