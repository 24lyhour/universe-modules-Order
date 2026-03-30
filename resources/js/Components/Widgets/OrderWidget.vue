<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer } from '@/components/ui/chart';
import {
    VisXYContainer,
    VisGroupedBar,
    VisAxis,
    VisSingleContainer,
    VisDonut,
    VisArea,
    VisLine,
    VisStackedBar,
} from '@unovis/vue';
import {
    ShoppingCart,
    TrendingUp,
    TrendingDown,
    DollarSign,
    Clock,
    CheckCircle,
    XCircle,
    RefreshCw,
    Calendar,
    Package,
    Truck,
    CreditCard,
    Users,
} from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import type { ChartConfig } from '@/components/ui/chart';

export interface OrderMetrics {
    total: number;
    completed: number;
    pending: number;
    cancelled: number;
    total_revenue: number;
    average_order_value: number;
    growth_percent: number;
    today_orders: number;
    today_revenue: number;
}

export interface StatusBreakdown {
    pending: number;
    confirmed: number;
    preparing: number;
    ready: number;
    delivering: number;
    delivered: number;
    completed: number;
    cancelled: number;
    refunded: number;
}

export interface PaymentBreakdown {
    statuses: Record<string, number>;
    total_paid: number;
}

export interface RevenueTrendPoint {
    label: string;
    orders: number;
    revenue: number;
    completed: number;
}

export interface DailyTrendPoint {
    label: string;
    date: string;
    orders: number;
    revenue: number;
}

export interface RecentOrder {
    id: number;
    uuid: string;
    order_number: string;
    customer_name: string;
    outlet_name: string;
    total_amount: number;
    status: string;
    payment_status: string;
    created_at: string;
}

export interface OrderWidgetProps {
    metrics: OrderMetrics;
    statusBreakdown?: StatusBreakdown;
    paymentBreakdown?: PaymentBreakdown;
    revenueTrend?: RevenueTrendPoint[];
    dailyTrend?: DailyTrendPoint[];
    recentOrders?: RecentOrder[];
    dateRange?: string;
    loading?: boolean;
}

const props = withDefaults(defineProps<OrderWidgetProps>(), {
    dateRange: '30d',
    loading: false,
    statusBreakdown: () => ({}) as any,
    paymentBreakdown: () => ({}) as any,
    revenueTrend: () => [],
    dailyTrend: () => [],
    recentOrders: () => [],
});

const emit = defineEmits<{
    (e: 'dateRangeChange', value: string): void;
    (e: 'refresh'): void;
}>();

const selectedDateRange = ref(props.dateRange);

const dateRangeOptions = [
    { value: 'today', label: 'Today' },
    { value: '7d', label: 'Last 7 Days' },
    { value: '30d', label: 'Last 30 Days' },
    { value: '90d', label: 'Last 90 Days' },
    { value: 'year', label: 'This Year' },
];

watch(selectedDateRange, (newValue) => {
    emit('dateRangeChange', newValue);
});

const handleRefresh = () => {
    emit('refresh');
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

const cancellationRate = computed(() => {
    return props.metrics.total > 0
        ? Math.round((props.metrics.cancelled / props.metrics.total) * 100)
        : 0;
});

// Order status donut data
const statusDonutData = computed(() => {
    const sb = props.statusBreakdown;
    if (!sb || Object.keys(sb).length === 0) {
        return [
            { status: 'completed', label: 'Completed', value: props.metrics.completed, color: '#10b981' },
            { status: 'pending', label: 'Pending', value: props.metrics.pending, color: '#f59e0b' },
            { status: 'cancelled', label: 'Cancelled', value: props.metrics.cancelled, color: '#ef4444' },
        ];
    }

    const statusColors: Record<string, string> = {
        pending: '#f59e0b',
        confirmed: '#3b82f6',
        preparing: '#8b5cf6',
        ready: '#06b6d4',
        delivering: '#f97316',
        delivered: '#14b8a6',
        completed: '#10b981',
        cancelled: '#ef4444',
        refunded: '#6b7280',
    };

    const statusLabels: Record<string, string> = {
        pending: 'Pending',
        confirmed: 'Confirmed',
        preparing: 'Preparing',
        ready: 'Ready',
        delivering: 'Delivering',
        delivered: 'Delivered',
        completed: 'Completed',
        cancelled: 'Cancelled',
        refunded: 'Refunded',
    };

    return Object.entries(sb)
        .filter(([_, value]) => (value as number) > 0)
        .map(([status, value]) => ({
            status,
            label: statusLabels[status] || status,
            value: value as number,
            color: statusColors[status] || '#6b7280',
        }));
});

// Payment status donut data
const paymentDonutData = computed(() => {
    const pb = props.paymentBreakdown?.statuses;
    if (!pb) return [];

    const paymentColors: Record<string, string> = {
        paid: '#10b981',
        pending: '#f59e0b',
        failed: '#ef4444',
        refunded: '#6b7280',
        partial: '#8b5cf6',
    };

    const paymentLabels: Record<string, string> = {
        paid: 'Paid',
        pending: 'Pending',
        failed: 'Failed',
        refunded: 'Refunded',
        partial: 'Partial',
    };

    return Object.entries(pb)
        .filter(([_, value]) => (value as number) > 0)
        .map(([status, value]) => ({
            status,
            label: paymentLabels[status] || status,
            value: value as number,
            color: paymentColors[status] || '#6b7280',
        }));
});

// Chart configs
const revenueChartConfig: ChartConfig = {
    revenue: { label: 'Revenue', color: 'var(--chart-1)' },
    orders: { label: 'Orders', color: 'var(--chart-2)' },
};

const dailyChartConfig: ChartConfig = {
    orders: { label: 'Orders', color: 'var(--chart-1)' },
    revenue: { label: 'Revenue', color: 'var(--chart-2)' },
};

const orderStatusChartConfig: ChartConfig = {
    completed: { label: 'Completed', color: '#10b981' },
    pending: { label: 'Pending', color: '#f59e0b' },
    cancelled: { label: 'Cancelled', color: '#ef4444' },
};

// Formatters
const formatNumber = (num: number) => new Intl.NumberFormat().format(num);

const formatCurrency = (num: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
}).format(num);

const formatCurrencyFull = (num: number) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(num);

const formatPercent = (num: number) => `${num.toFixed(1)}%`;

const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const map: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        completed: 'default',
        delivered: 'default',
        pending: 'secondary',
        confirmed: 'secondary',
        preparing: 'outline',
        ready: 'outline',
        delivering: 'outline',
        cancelled: 'destructive',
        refunded: 'destructive',
        paid: 'default',
        failed: 'destructive',
        partial: 'outline',
    };
    return map[status] || 'outline';
};

const getStatusColor = (status: string) => {
    const map: Record<string, string> = {
        pending: 'text-amber-600',
        confirmed: 'text-blue-600',
        preparing: 'text-purple-600',
        ready: 'text-cyan-600',
        delivering: 'text-orange-600',
        delivered: 'text-teal-600',
        completed: 'text-green-600',
        cancelled: 'text-red-600',
        refunded: 'text-gray-600',
        paid: 'text-green-600',
        failed: 'text-red-600',
        partial: 'text-purple-600',
    };
    return map[status] || 'text-gray-600';
};

const timeAgo = (dateStr: string) => {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now.getTime() - date.getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header with Date Filter -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight">Order Performance Metrics</h2>
                <p class="text-sm text-muted-foreground">Track orders, revenue, and fulfillment overview</p>
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

        <!-- Primary Metrics Grid -->
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
                            :is="growthTrend.isPositive ? TrendingUp : TrendingDown"
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
                        {{ formatCurrencyFull(metrics.average_order_value) }} avg order
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
                        {{ metrics.total > 0 ? formatPercent((metrics.pending / metrics.total) * 100) : '0%' }} of total
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Secondary Metrics -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Today's Orders -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Today's Orders</CardTitle>
                    <Package class="h-4 w-4 text-indigo-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-indigo-600">{{ formatNumber(metrics.today_orders) }}</div>
                    <p class="text-xs text-muted-foreground">{{ formatCurrency(metrics.today_revenue) }} revenue</p>
                </CardContent>
            </Card>

            <!-- Cancelled Orders -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Cancelled</CardTitle>
                    <XCircle class="h-4 w-4 text-red-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ formatNumber(metrics.cancelled) }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ cancellationRate }}% cancellation rate
                    </p>
                </CardContent>
            </Card>

            <!-- Average Order Value -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Avg Order Value</CardTitle>
                    <CreditCard class="h-4 w-4 text-purple-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-purple-600">{{ formatCurrencyFull(metrics.average_order_value) }}</div>
                    <p class="text-xs text-muted-foreground">per completed order</p>
                </CardContent>
            </Card>

            <!-- Completion Rate -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Fulfillment Rate</CardTitle>
                    <Truck class="h-4 w-4 text-teal-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-teal-600">{{ completionRate }}%</div>
                    <div class="mt-1 h-2 w-full rounded-full bg-muted">
                        <div
                            class="h-2 rounded-full bg-teal-500 transition-all"
                            :style="{ width: `${completionRate}%` }"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Revenue Trend + Daily Orders -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Revenue Trend (6 months) -->
            <Card v-if="revenueTrend.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <DollarSign class="h-5 w-5" />
                        Revenue Trend
                    </CardTitle>
                    <CardDescription>Monthly revenue and order count</CardDescription>
                </CardHeader>
                <CardContent>
                    <ChartContainer :config="revenueChartConfig" class="h-[280px]">
                        <VisXYContainer :data="revenueTrend" :margin="{ top: 10, bottom: 30, left: 60, right: 10 }">
                            <VisArea
                                :x="(_: RevenueTrendPoint, i: number) => i"
                                :y="(d: RevenueTrendPoint) => d.revenue"
                                :color="revenueChartConfig.revenue.color"
                                :opacity="0.3"
                            />
                            <VisLine
                                :x="(_: RevenueTrendPoint, i: number) => i"
                                :y="(d: RevenueTrendPoint) => d.revenue"
                                :color="revenueChartConfig.revenue.color"
                                :line-width="2"
                            />
                            <VisAxis type="x" :tick-format="(i: number) => revenueTrend[i]?.label ?? ''" />
                            <VisAxis type="y" :tick-format="(v: number) => `$${v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v.toFixed(0)}`" />
                        </VisXYContainer>
                    </ChartContainer>
                </CardContent>
            </Card>

            <!-- Daily Orders (7 days) -->
            <Card v-if="dailyTrend.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShoppingCart class="h-5 w-5" />
                        Daily Order Volume
                    </CardTitle>
                    <CardDescription>Orders per day (last 7 days)</CardDescription>
                </CardHeader>
                <CardContent>
                    <ChartContainer :config="dailyChartConfig" class="h-[280px]">
                        <VisXYContainer :data="dailyTrend" :margin="{ top: 10, bottom: 30, left: 40, right: 10 }">
                            <VisGroupedBar
                                :x="(_: DailyTrendPoint, i: number) => i"
                                :y="[(d: DailyTrendPoint) => d.orders]"
                                :color="[dailyChartConfig.orders.color]"
                                :bar-padding="0.3"
                                :rounded-corners="4"
                            />
                            <VisAxis type="x" :tick-format="(i: number) => dailyTrend[i]?.label ?? ''" />
                            <VisAxis type="y" />
                        </VisXYContainer>
                    </ChartContainer>
                </CardContent>
            </Card>
        </div>

        <!-- Order Status + Payment Status Distribution -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Order Status Distribution -->
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
                        <ChartContainer
                            :config="orderStatusChartConfig"
                            class="h-[200px]"
                            :style="{
                                '--vis-donut-central-label-font-size': 'var(--text-2xl)',
                                '--vis-donut-central-label-font-weight': 'var(--font-weight-bold)',
                                '--vis-donut-central-label-text-color': 'var(--foreground)',
                                '--vis-donut-central-sub-label-text-color': 'var(--muted-foreground)',
                            }"
                        >
                            <VisSingleContainer :data="statusDonutData" :margin="{ top: 10, bottom: 10 }">
                                <VisDonut
                                    :value="(d: any) => d.value"
                                    :color="(d: any) => d.color"
                                    :arc-width="40"
                                    :pad-angle="0.02"
                                    :corner-radius="4"
                                    :central-label="metrics.total.toLocaleString()"
                                    central-sub-label="Orders"
                                />
                            </VisSingleContainer>
                        </ChartContainer>

                        <div class="flex flex-col justify-center space-y-2">
                            <div
                                v-for="item in statusDonutData"
                                :key="item.status"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <span class="text-sm">{{ item.label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ formatNumber(item.value) }}</span>
                                    <Badge :variant="getStatusBadgeVariant(item.status)" class="text-xs">
                                        {{ metrics.total > 0 ? formatPercent((item.value / metrics.total) * 100) : '0%' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment Status Distribution -->
            <Card v-if="paymentDonutData.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CreditCard class="h-5 w-5" />
                        Payment Status
                    </CardTitle>
                    <CardDescription>
                        {{ formatCurrency(paymentBreakdown?.total_paid ?? 0) }} total collected
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <ChartContainer
                            :config="orderStatusChartConfig"
                            class="h-[200px]"
                            :style="{
                                '--vis-donut-central-label-font-size': 'var(--text-2xl)',
                                '--vis-donut-central-label-font-weight': 'var(--font-weight-bold)',
                                '--vis-donut-central-label-text-color': 'var(--foreground)',
                                '--vis-donut-central-sub-label-text-color': 'var(--muted-foreground)',
                            }"
                        >
                            <VisSingleContainer :data="paymentDonutData" :margin="{ top: 10, bottom: 10 }">
                                <VisDonut
                                    :value="(d: any) => d.value"
                                    :color="(d: any) => d.color"
                                    :arc-width="40"
                                    :pad-angle="0.02"
                                    :corner-radius="4"
                                    :central-label="metrics.total.toLocaleString()"
                                    central-sub-label="Payments"
                                />
                            </VisSingleContainer>
                        </ChartContainer>

                        <div class="flex flex-col justify-center space-y-2">
                            <div
                                v-for="item in paymentDonutData"
                                :key="item.status"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <span class="text-sm">{{ item.label }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ formatNumber(item.value) }}</span>
                                    <Badge :variant="getStatusBadgeVariant(item.status)" class="text-xs">
                                        {{ metrics.total > 0 ? formatPercent((item.value / metrics.total) * 100) : '0%' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Orders -->
        <Card v-if="recentOrders.length > 0">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="h-5 w-5" />
                    Recent Orders
                </CardTitle>
                <CardDescription>Latest orders placed</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10">
                                <ShoppingCart class="h-4 w-4 text-primary" />
                            </div>
                            <div>
                                <p class="text-sm font-medium">{{ order.order_number }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ order.customer_name }} &middot; {{ order.outlet_name }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-right">
                            <div>
                                <p class="text-sm font-medium">{{ formatCurrencyFull(order.total_amount) }}</p>
                                <p class="text-xs text-muted-foreground">{{ timeAgo(order.created_at) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <Badge :variant="getStatusBadgeVariant(order.status)" class="text-xs capitalize">
                                    {{ order.status }}
                                </Badge>
                                <Badge :variant="getStatusBadgeVariant(order.payment_status)" class="text-xs capitalize">
                                    {{ order.payment_status }}
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
                        {{ cancellationRate }}% cancellation rate — review reasons to improve retention
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
