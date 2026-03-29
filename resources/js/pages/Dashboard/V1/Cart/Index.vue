<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, type VNode } from 'vue';
import {
    Plus,
    Eye,
    Pencil,
    Trash2,
    ShoppingCart,
    Clock,
    CheckCircle,
    XCircle,
    ArrowRightCircle,
    X,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
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
import type { CartIndexProps, CartItem } from '@order/types';

// Persistent layout required for momentum-modal
defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Carts', href: '/dashboard/carts' },
        ]}, () => page),
});

const props = defineProps<CartIndexProps>();

// State
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const activeFilter = ref(props.filters.is_active !== undefined ? String(props.filters.is_active) : 'all');
const outletFilter = ref(props.filters.outlet_id?.toString() || 'all');
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);
const selectedItem = ref<CartItem | null>(null);

// Pagination computed
const pagination = computed(() => ({
    current_page: props.cartItems.meta.current_page,
    last_page: props.cartItems.meta.last_page,
    per_page: props.cartItems.meta.per_page,
    total: props.cartItems.meta.total,
}));

// Table columns
const columns: TableColumn<CartItem>[] = [
    { key: 'uuid', label: 'Cart ID', width: '20%' },
    { key: 'customer', label: 'Customer', width: '20%' },
    { key: 'items_count', label: 'Items', width: '10%', align: 'center' },
    { key: 'total_amount', label: 'Total', width: '12%', align: 'right' },
    { key: 'status', label: 'Status', width: '12%' },
    { key: 'is_active', label: 'Active', width: '12%' },
    { key: 'created_at', label: 'Date', width: '14%' },
];

// Table actions
const tableActions: TableAction<CartItem>[] = [
    {
        label: 'View',
        icon: Eye,
        onClick: (item) => router.visit(`/dashboard/carts/${item.id}`),
    },
    {
        label: 'Edit',
        icon: Pencil,
        onClick: (item) => router.visit(`/dashboard/carts/${item.id}/edit`),
    },
    {
        label: 'Convert to Order',
        icon: ArrowRightCircle,
        onClick: (item) => handleConvertToOrder(item),
        show: (item: CartItem) => item.status === 'active' && (item.items_count ?? 0) > 0,
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

// Status badge variants
const getStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        active: 'default',
        abandoned: 'outline',
        converted: 'secondary',
        expired: 'destructive',
    };
    return variants[status] || 'outline';
};

// Handlers
const handleCreate = () => router.visit('/dashboard/carts/create');

const applyFilters = (overrides: { page?: number; per_page?: number } = {}) => {
    router.get('/dashboard/carts', {
        search: searchQuery.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        is_active: activeFilter.value !== 'all' ? activeFilter.value : undefined,
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

const handleActiveFilter = (value: string | number | boolean | bigint | Record<string, unknown> | null | undefined) => {
    activeFilter.value = String(value || 'all');
    applyFilters({ page: 1 });
};

const handleOutletFilter = (value: string | number | boolean | bigint | Record<string, unknown> | null | undefined) => {
    outletFilter.value = String(value || 'all');
    applyFilters({ page: 1 });
};

const handleDelete = () => {
    if (!selectedItem.value) return;
    isDeleting.value = true;
    router.delete(`/dashboard/carts/${selectedItem.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            selectedItem.value = null;
            toast.success('Cart deleted successfully.');
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const handleRowClick = (item: CartItem) => {
    router.visit(`/dashboard/carts/${item.id}`);
};

const handleStatusToggle = (item: CartItem, newStatus: boolean) => {
    router.put(`/dashboard/carts/${item.id}/toggle-status`, {
        is_active: newStatus,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Cart ${newStatus ? 'activated' : 'deactivated'} successfully.`);
        },
    });
};

const handleConvertToOrder = (item: CartItem) => {
    router.post(`/dashboard/carts/${item.id}/convert-to-order`, {}, {
        onSuccess: () => {
            toast.success('Cart converted to order successfully.');
        },
    });
};

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return !!(searchQuery.value || statusFilter.value !== 'all' || activeFilter.value !== 'all' || outletFilter.value !== 'all');
});

const handleClearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    activeFilter.value = 'all';
    outletFilter.value = 'all';
    router.get('/dashboard/carts', {}, { preserveState: true, preserveScroll: true });
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

const truncateUuid = (uuid: string) => {
    return uuid.substring(0, 8) + '...';
};
</script>

<template>
    <Head title="Carts" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Carts</h1>
                <p class="text-muted-foreground">Manage shopping carts</p>
            </div>
            <div class="flex items-center gap-2">
                <Button @click="handleCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Cart
                </Button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-5">
            <StatsCard
                title="Total Carts"
                :value="stats.total"
                :icon="ShoppingCart"
                icon-color="text-blue-500"
            />
            <StatsCard
                title="Active"
                :value="stats.active"
                :icon="CheckCircle"
                icon-color="text-green-500"
                value-color="text-green-600"
            />
            <StatsCard
                title="Abandoned"
                :value="stats.abandoned"
                :icon="Clock"
                icon-color="text-yellow-500"
                value-color="text-yellow-600"
            />
            <StatsCard
                title="Converted"
                :value="stats.converted"
                :icon="ArrowRightCircle"
                icon-color="text-blue-500"
                value-color="text-blue-600"
            />
            <StatsCard
                title="Expired"
                :value="stats.expired"
                :icon="XCircle"
                icon-color="text-gray-500"
                value-color="text-gray-600"
            />
        </div>

        <!-- Table -->
        <TableReusable
            v-model:search-query="searchQuery"
            :data="props.cartItems.data"
            :columns="columns"
            :actions="tableActions"
            :pagination="pagination"
            search-placeholder="Search carts..."
            empty-message="No carts found."
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

                    <!-- Active Filter -->
                    <Select :model-value="activeFilter" @update:model-value="handleActiveFilter">
                        <SelectTrigger class="w-[150px]">
                            <SelectValue placeholder="All Active" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="true">Active</SelectItem>
                            <SelectItem value="false">Inactive</SelectItem>
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
            <template #cell-uuid="{ item }">
                <div class="font-mono text-sm text-muted-foreground">
                    {{ truncateUuid(item.uuid) }}
                </div>
            </template>

            <template #cell-customer="{ item }">
                <div v-if="item.customer">
                    <div class="font-medium">{{ item.customer.name }}</div>
                    <div class="text-xs text-muted-foreground">{{ item.customer.email }}</div>
                </div>
                <span v-else class="text-muted-foreground">Guest</span>
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

            <template #cell-is_active="{ item }">
                <div class="flex items-center gap-2" @click.stop>
                    <Switch
                        :model-value="item.is_active"
                        @update:model-value="handleStatusToggle(item, $event)"
                    />
                </div>
            </template>

            <template #cell-created_at="{ item }">
                <span class="text-sm text-muted-foreground">{{ formatDate(item.created_at) }}</span>
            </template>
        </TableReusable>
    </div>

    <!-- Delete Confirmation Modal -->
    <ModalConfirm
        v-model:open="isDeleteModalOpen"
        title="Delete Cart"
        :description="`Are you sure you want to delete this cart? This action cannot be undone.`"
        confirm-text="Delete"
        variant="danger"
        :loading="isDeleting"
        @confirm="handleDelete"
    />
</template>
