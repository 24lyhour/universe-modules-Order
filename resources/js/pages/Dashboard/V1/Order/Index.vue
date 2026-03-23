<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, type VNode } from 'vue';
import {
    Plus,
    Eye,
    Pencil,
    Trash2,
    Package,
    Clock,
    CheckCircle,
    Truck,
    X,
    DollarSign,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from '@/composables/useToast';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    TableReusable,
    ModalConfirm,
    StatsCard,
    type TableColumn,
    type TableAction,
} from '@/components/shared';
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

// Pagination computed
const pagination = computed(() => ({
    current_page: props.orderItems.meta.current_page,
    last_page: props.orderItems.meta.last_page,
    per_page: props.orderItems.meta.per_page,
    total: props.orderItems.meta.total,
}));

// Table columns
const columns: TableColumn<OrderItem>[] = [
    { key: 'order_number', label: 'Order #', width: '12%' },
    { key: 'customer', label: 'Customer', width: '15%' },
    { key: 'outlet', label: 'Outlet', width: '12%' },
    { key: 'items_count', label: 'Items', width: '6%', align: 'center' },
    { key: 'total_amount', label: 'Total', width: '10%', align: 'right' },
    { key: 'status', label: 'Status', width: '10%' },
    { key: 'payment_status', label: 'Payment', width: '10%' },
    { key: 'payment_method', label: 'Method', width: '10%' },
    { key: 'created_at', label: 'Date', width: '10%' },
];

// Table actions
const tableActions: TableAction<OrderItem>[] = [
    {
        label: 'View',
        icon: Eye,
        onClick: (item) => router.visit(`/dashboard/orders/${item.id}`),
    },
    {
        label: 'Edit',
        icon: Pencil,
        onClick: (item) => router.visit(`/dashboard/orders/${item.id}/edit`),
    },
    {
        label: 'Delete',
        icon: Trash2,
        onClick: (item) => {
            selectedItem.value = item;
            isDeleteModalOpen.value = true;
        },
        variant: 'destructive',
        separator: true,
    },
];

// Status badge variants (Cambodia e-commerce flow)
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

const handleStatusFilter = (value: string | number | boolean | bigint | Record<string, unknown> | null | undefined) => {
    statusFilter.value = String(value || 'all');
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
    router.delete(`/dashboard/orders/${selectedItem.value.id}`, {
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
    router.visit(`/dashboard/orders/${item.id}`);
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
</script>

<template>
    <Head title="Orders" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Orders</h1>
                <p class="text-muted-foreground">Manage customer orders</p>
            </div>
            <div class="flex items-center gap-2">
                <Button @click="handleCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Order
                </Button>
            </div>
        </div>

        <!-- Stats Cards (Cambodia E-commerce Flow) -->
        <div class="grid gap-4 md:grid-cols-6">
            <StatsCard
                title="Total Orders"
                :value="stats.total"
                :icon="Package"
                icon-color="text-blue-500"
            />
            <StatsCard
                title="Pending"
                :value="stats.pending"
                :icon="Clock"
                icon-color="text-yellow-500"
                value-color="text-yellow-600"
            />
            <StatsCard
                title="Preparing"
                :value="stats.preparing ?? 0"
                :icon="Package"
                icon-color="text-orange-500"
                value-color="text-orange-600"
            />
            <StatsCard
                title="Delivering"
                :value="stats.delivering ?? 0"
                :icon="Truck"
                icon-color="text-purple-500"
                value-color="text-purple-600"
            />
            <StatsCard
                title="Completed"
                :value="stats.completed ?? 0"
                :icon="CheckCircle"
                icon-color="text-green-500"
                value-color="text-green-600"
            />
            <StatsCard
                title="Revenue"
                :value="formatCurrency(stats.total_revenue)"
                :icon="DollarSign"
                icon-color="text-green-500"
                value-color="text-green-600"
            />
        </div>

        <!-- Table -->
        <TableReusable
            v-model:search-query="searchQuery"
            :data="props.orderItems.data"
            :columns="columns"
            :actions="tableActions"
            :pagination="pagination"
            search-placeholder="Search orders..."
            empty-message="No orders found."
            @page-change="handlePageChange"
            @per-page-change="handlePerPageChange"
            @search="handleSearch"
            @row-click="handleRowClick"
        >
            <!-- Toolbar slot for filters -->
            <template #toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Status Filter -->
                    <Select :model-value="statusFilter" @update:model-value="handleStatusFilter">
                        <SelectTrigger class="w-[150px]">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem
                                v-for="status in props.statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Payment Status Filter -->
                    <Select :model-value="paymentStatusFilter" @update:model-value="handlePaymentStatusFilter">
                        <SelectTrigger class="w-[150px]">
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
                        <SelectTrigger class="w-[180px]">
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
                        Clear Filters
                    </Button>
                </div>
            </template>

            <!-- Custom cell slots -->
            <template #cell-order_number="{ item }">
                <div class="font-mono text-sm font-medium">
                    {{ item.order_number }}
                </div>
            </template>

            <template #cell-customer="{ item }">
                <div v-if="item.customer">
                    <div class="font-medium">{{ item.customer.name }}</div>
                    <div class="text-xs text-muted-foreground">{{ item.customer.email }}</div>
                </div>
                <span v-else class="text-muted-foreground">Guest</span>
            </template>

            <template #cell-outlet="{ item }">
                <div v-if="item.outlet" class="text-sm">
                    {{ item.outlet.name }}
                </div>
                <span v-else class="text-muted-foreground">-</span>
            </template>

            <template #cell-items_count="{ item }">
                <Badge variant="outline" class="tabular-nums">
                    {{ item.items_count ?? 0 }}
                </Badge>
            </template>

            <template #cell-total_amount="{ item }">
                <span class="font-medium tabular-nums">{{ formatCurrency(item.total_amount ?? 0) }}</span>
            </template>

            <template #cell-status="{ item }">
                <Badge :variant="getStatusVariant(item.status)" class="capitalize">
                    {{ item.status }}
                </Badge>
            </template>

            <template #cell-payment_status="{ item }">
                <Badge :variant="getPaymentStatusVariant(item.payment_status)" class="capitalize">
                    {{ item.payment_status }}
                </Badge>
            </template>

            <template #cell-payment_method="{ item }">
                <span class="text-sm capitalize">{{ item.payment_method?.replace('_', ' ') || '-' }}</span>
            </template>

            <template #cell-created_at="{ item }">
                <span class="text-sm text-muted-foreground">{{ formatDate(item.created_at) }}</span>
            </template>
        </TableReusable>
    </div>

    <!-- Delete Confirmation Modal -->
    <ModalConfirm
        v-model:open="isDeleteModalOpen"
        title="Delete Order"
        :description="`Are you sure you want to delete order ${selectedItem?.order_number}? This action cannot be undone.`"
        confirm-text="Delete"
        variant="danger"
        :loading="isDeleting"
        @confirm="handleDelete"
    />
</template>
