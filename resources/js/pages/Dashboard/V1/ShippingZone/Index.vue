<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, type VNode } from 'vue';
import { MapPin, Plus, Pencil, Trash2, Eye, Circle, Hexagon, Truck, Search, X, DollarSign, Clock } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ModalConfirm, StatsCard, CardWidget, Pagination } from '@/components/shared';
import type { ShippingZoneIndexProps, ShippingZone } from '@order/types';

defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Shipping Zones', href: '#' },
        ]}, () => page),
});

const props = defineProps<ShippingZoneIndexProps>();

// Filters - use 'all' instead of empty string for Select components
const search = ref(props.filters.search || '');
const outletId = ref(props.filters.outlet_id?.toString() || 'all');
const zoneType = ref(props.filters.zone_type || 'all');
const vehicleType = ref(props.filters.vehicle_type || 'all');
const isActive = ref(props.filters.is_active || 'all');

// Delete modal
const showDeleteModal = ref(false);
const zoneToDelete = ref<ShippingZone | null>(null);
const isDeleting = ref(false);

// Card actions for CardWidget
const getCardActions = (zone: ShippingZone) => [
    {
        label: 'View',
        icon: Eye,
        onClick: () => router.visit(`/dashboard/shipping-zones/${zone.uuid}`),
    },
    {
        label: 'Edit',
        icon: Pencil,
        onClick: () => router.visit(`/dashboard/shipping-zones/${zone.uuid}/edit`),
    },
    {
        label: 'Delete',
        icon: Trash2,
        variant: 'destructive' as const,
        separator: true,
        onClick: () => confirmDelete(zone),
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const applyFilters = () => {
    router.get('/dashboard/shipping-zones', {
        search: search.value || undefined,
        outlet_id: outletId.value === 'all' ? undefined : outletId.value,
        zone_type: zoneType.value === 'all' ? undefined : zoneType.value,
        vehicle_type: vehicleType.value === 'all' ? undefined : vehicleType.value,
        is_active: isActive.value === 'all' ? undefined : isActive.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    outletId.value = 'all';
    zoneType.value = 'all';
    vehicleType.value = 'all';
    isActive.value = 'all';
    applyFilters();
};

const handlePageChange = (page: number) => {
    router.get('/dashboard/shipping-zones', {
        ...props.filters,
        page,
    }, {
        preserveState: true,
        replace: true,
    });
};

const handleStatusToggle = (zone: ShippingZone, newStatus: boolean) => {
    router.put(`/dashboard/shipping-zones/${zone.uuid}/toggle-active`, {
        status: newStatus,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmDelete = (zone: ShippingZone) => {
    zoneToDelete.value = zone;
    showDeleteModal.value = true;
};

const handleDelete = () => {
    if (!zoneToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/dashboard/shipping-zones/${zoneToDelete.value.uuid}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            zoneToDelete.value = null;
        },
    });
};

const handleCreate = () => {
    router.visit('/dashboard/shipping-zones/create');
};

// Stats from backend
const stats = computed(() => ({
    total: props.stats?.total ?? 0,
    active: props.stats?.active ?? 0,
    circle: props.stats?.by_type?.circle ?? 0,
    polygon: props.stats?.by_type?.polygon ?? 0,
}));
</script>

<template>
    <Head title="Shipping Zones" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Shipping Zones</h1>
                <p class="text-muted-foreground">Manage delivery zones for your outlets</p>
            </div>
            <Button @click="handleCreate" class="gap-2">
                <Plus class="h-4 w-4" />
                Add Zone
            </Button>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <StatsCard
                title="Total Zones"
                :value="stats.total"
                :icon="MapPin"
                variant="info"
            />
            <StatsCard
                title="Active Zones"
                :value="stats.active"
                :icon="Truck"
                variant="success"
            />
            <StatsCard
                title="Circle Zones"
                :value="stats.circle"
                :icon="Circle"
                variant="secondary"
            />
            <StatsCard
                title="Polygon Zones"
                :value="stats.polygon"
                :icon="Hexagon"
                variant="secondary"
            />
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Search zones..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Select v-model="outletId" @update:model-value="applyFilters">
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
            <Select v-model="zoneType" @update:model-value="applyFilters">
                <SelectTrigger class="w-[140px]">
                    <SelectValue placeholder="Zone Type" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Types</SelectItem>
                    <SelectItem
                        v-for="(label, value) in props.zoneTypes"
                        :key="value"
                        :value="String(value)"
                    >
                        {{ label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="vehicleType" @update:model-value="applyFilters">
                <SelectTrigger class="w-[140px]">
                    <SelectValue placeholder="Vehicle" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Vehicles</SelectItem>
                    <SelectItem
                        v-for="(label, value) in props.vehicleTypes"
                        :key="value"
                        :value="String(value)"
                    >
                        {{ label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="isActive" @update:model-value="applyFilters">
                <SelectTrigger class="w-[120px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Status</SelectItem>
                    <SelectItem value="true">Active</SelectItem>
                    <SelectItem value="false">Inactive</SelectItem>
                </SelectContent>
            </Select>
            <Button variant="ghost" size="sm" @click="clearFilters" class="text-muted-foreground hover:text-foreground">
                <X class="mr-1 h-4 w-4" />
                Clear
            </Button>
        </div>

        <!-- Card Grid -->
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <CardWidget
                    v-for="zone in props.shippingZones.data"
                    :key="zone.uuid"
                    :actions="getCardActions(zone)"
                    @click="router.visit(`/dashboard/shipping-zones/${zone.uuid}`)"
                >
                    <template #header-icon>
                        <div
                            class="w-4 h-4 rounded-full shrink-0"
                            :style="{ backgroundColor: zone.color }"
                        />
                    </template>
                    <template #header-title>
                        <span class="font-semibold truncate">{{ zone.name }}</span>
                    </template>
                    <template #header-badge>
                        <Switch
                            :model-value="zone.is_active"
                            @update:model-value="handleStatusToggle(zone, $event)"
                            @click.stop
                        />
                    </template>

                    <template #body>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <MapPin class="h-3.5 w-3.5" />
                                <span>{{ zone.outlet?.name || 'No outlet' }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <Badge variant="outline" class="capitalize">
                                    <Circle v-if="zone.zone_type === 'circle'" class="h-3 w-3 mr-1" />
                                    <Hexagon v-else class="h-3 w-3 mr-1" />
                                    {{ zone.zone_type }}
                                </Badge>
                                <Badge variant="secondary" class="capitalize">
                                    <Truck class="h-3 w-3 mr-1" />
                                    {{ zone.vehicle_type }}
                                </Badge>
                            </div>
                            <div v-if="zone.zone_type === 'circle' && zone.radius" class="text-muted-foreground">
                                Radius: {{ zone.radius >= 1000 ? `${(zone.radius / 1000).toFixed(1)}km` : `${zone.radius}m` }}
                            </div>
                        </div>
                    </template>

                    <template #footer-left>
                        <div class="flex items-center gap-1 text-sm">
                            <DollarSign class="h-3.5 w-3.5 text-muted-foreground" />
                            <span class="font-medium">{{ formatCurrency(zone.delivery_fee) }}</span>
                        </div>
                    </template>
                    <template #footer-right>
                        <div v-if="zone.estimated_delivery_minutes" class="flex items-center gap-1 text-sm text-muted-foreground">
                            <Clock class="h-3.5 w-3.5" />
                            <span>~{{ zone.estimated_delivery_minutes }} min</span>
                        </div>
                        <Badge v-else variant="outline" class="text-xs">
                            Priority: {{ zone.priority }}
                        </Badge>
                    </template>
                </CardWidget>
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="props.shippingZones.meta.last_page > 1"
                :current-page="props.shippingZones.meta.current_page"
                :last-page="props.shippingZones.meta.last_page"
                :per-page="props.shippingZones.meta.per_page"
                :total="props.shippingZones.meta.total"
                @page-change="handlePageChange"
            />
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ModalConfirm
        v-model:open="showDeleteModal"
        title="Delete Shipping Zone"
        :description="`Are you sure you want to delete '${zoneToDelete?.name}'? This action cannot be undone.`"
        confirm-text="Delete"
        variant="danger"
        :loading="isDeleting"
        @confirm="handleDelete"
    />
</template>
