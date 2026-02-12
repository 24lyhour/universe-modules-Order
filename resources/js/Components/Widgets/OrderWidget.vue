<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer } from '@/components/ui/chart';
import {
    VisXYContainer,
    VisGroupedBar,
    VisAxis,
    VisSingleContainer,
    VisDonut,
} from '@unovis/vue';
import {
    ShoppingCart,
    TrendingUp,
    DollarSign,
    Clock,
    CheckCircle,
    XCircle,
    RefreshCw,
    Calendar,
} from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useChartColors } from '@/composables/useChartColors';
import type { ChartConfig } from '@/components/ui/chart';

export interface OrderMetrics {
    total: number;
    completed: number;
    pending: number;
    cancelled: number;
    total_revenue: number;
    average_order_value: number;
    growth_percent: number;
}

export interface OrderStatusDistribution {
    status: string;
    label: string;
    value: number;
    color: string;
}

export interface OrderWidgetProps {
    metrics: OrderMetrics;
    statusDistribution?: OrderStatusDistribution[];
    dateRange?: string;
    loading?: boolean;
}

const props = withDefaults(defineProps<OrderWidgetProps>(), {
    dateRange: '30d',
    loading: false,
    statusDistribution: () => [],
});

const emit = defineEmits<{
    (e: 'dateRangeChange', value: string): void;
    (e: 'refresh'): void;
}>();

const selectedDateRange = ref(props.dateRange);
const { chartColors } = useChartColors();

// Date range options
const dateRangeOptions = [
    { value: 'today', label: 'Today' },
    { value: '7d', label: 'Last 7 Days' },
    { value: '30d', label: 'Last 30 Days' },
    { value: '90d', label: 'Last 90 Days' },
    { value: 'year', label: 'This Year' },
];

/**
 * Order status distribution data
 */
const orderStatusData = computed(() => {
    if (props.statusDistribution.length > 0) {
        return props.statusDistribution;
    }
    return [
        { status: 'completed', label: 'Completed', value: props.metrics.completed, color: '#10b981' },
        { status: 'pending', label: 'Pending', value: props.metrics.pending, color: '#f59e0b' },
        { status: 'cancelled', label: 'Cancelled', value: props.metrics.cancelled, color: '#ef4444' },
    ];
});

const orderChartConfig: ChartConfig = {
    completed: { label: 'Completed', color: 'var(--chart-1)' },
    pending: { label: 'Pending', color: 'var(--chart-2)' },
    cancelled: { label: 'Cancelled', color: 'var(--chart-3)' },
};

// Computed metrics
const growthTrend = computed(() => ({
    isPositive: props.metrics.growth_percent >= 0,
    value: Math.abs(props.metrics.growth_percent),
}));

const completionRate = computed(() => {
    return props.metrics.total > 0
        ? Math.round((props.metrics.completed / props.metrics.total) * 100)
        : 0;
});

// Watch date range changes
watch(selectedDateRange, (newValue) => {
    emit('dateRangeChange', newValue);
});

// Methods
const handleRefresh = () => {
    emit('refresh');
};

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(num);
};

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatPercent = (num: number) => {
    return `${num.toFixed(1)}%`;
};

const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status.toLowerCase()) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header with Date Filter -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Order Performance Metrics</h2>
                <p class="text-sm text-muted-foreground">Track orders and revenue overview</p>
            </div>
            <div class="flex items-center gap-2">
                <Select v-model="selectedDateRange">
                    <SelectTrigger class="w-[160px]">
                        <Calendar class="mr-2 h-4 w-4" />
                        <SelectValue placeholder="Select period" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in dateRangeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" size="icon" @click="handleRefresh" :disabled="loading">
                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                </Button>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Total Orders -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Orders</CardTitle>
                    <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatNumber(metrics.total) }}</div>
                    <div class="flex items-center text-xs">
                        <component
                            :is="growthTrend.isPositive ? TrendingUp : TrendingUp"
                            class="mr-1 h-3 w-3"
                            :class="growthTrend.isPositive ? 'text-green-500' : 'text-red-500'"
                        />
                        <span :class="growthTrend.isPositive ? 'text-green-500' : 'text-red-500'">
                            {{ growthTrend.isPositive ? '+' : '-' }}{{ formatPercent(growthTrend.value) }}
                        </span>
                        <span class="ml-1 text-muted-foreground">vs previous period</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Completed Orders -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Completed</CardTitle>
                    <CheckCircle class="h-4 w-4 text-green-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ formatNumber(metrics.completed) }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ completionRate }}% completion rate
                    </p>
                </CardContent>
            </Card>

            <!-- Total Revenue -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                    <DollarSign class="h-4 w-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(metrics.total_revenue) }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ formatCurrency(metrics.average_order_value) }} average order
                    </p>
                </CardContent>
            </Card>

            <!-- Pending Orders -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pending</CardTitle>
                    <Clock class="h-4 w-4 text-amber-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-amber-600">{{ formatNumber(metrics.pending) }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ formatPercent((metrics.pending / metrics.total) * 100) }} of total
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Status Distribution Chart -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ShoppingCart class="h-5 w-5" />
                    Order Status Distribution
                </CardTitle>
                <CardDescription>Breakdown by order status</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Donut Chart -->
                    <ChartContainer
                        :config="orderChartConfig"
                        class="h-[200px]"
                        :style="{
                            '--vis-donut-central-label-font-size': 'var(--text-2xl)',
                            '--vis-donut-central-label-font-weight': 'var(--font-weight-bold)',
                            '--vis-donut-central-label-text-color': 'var(--foreground)',
                            '--vis-donut-central-sub-label-text-color': 'var(--muted-foreground)',
                        }"
                    >
                        <VisSingleContainer :data="orderStatusData" :margin="{ top: 10, bottom: 10 }">
                            <VisDonut
                                :value="(d: any) => d.value"
                                :color="(d: any) => {
                                    const colors = ['#10b981', '#f59e0b', '#ef4444'];
                                    const index = ['completed', 'pending', 'cancelled'].indexOf(d.status);
                                    return colors[index] || '#6b7280';
                                }"
                                :arc-width="40"
                                :pad-angle="0.02"
                                :corner-radius="4"
                                :central-label="metrics.total.toLocaleString()"
                                central-sub-label="Orders"
                            />
                        </VisSingleContainer>
                    </ChartContainer>

                    <!-- Legend -->
                    <div class="flex flex-col justify-center space-y-3">
                        <div
                            v-for="(item, index) in orderStatusData"
                            :key="item.status"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: item.color }"
                                ></span>
                                <span class="text-sm">{{ item.label }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ formatNumber(item.value) }}</span>
                                <Badge :variant="getStatusBadgeVariant(item.status)" class="text-xs">
                                    {{ formatPercent((item.value / metrics.total) * 100) }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Cancelled Orders Alert -->
        <Card
            v-if="metrics.cancelled > 0"
            class="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/20"
        >
            <CardContent class="flex items-center gap-4 pt-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50">
                    <XCircle class="h-5 w-5 text-red-600" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-red-800 dark:text-red-200">
                        {{ formatNumber(metrics.cancelled) }} orders cancelled
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400">
                        {{ formatPercent((metrics.cancelled / metrics.total) * 100) }} cancellation rate
                    </p>
                </div>
                <Button variant="outline" size="sm" class="border-red-300 text-red-700 hover:bg-red-100">
                    View Cancelled
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
