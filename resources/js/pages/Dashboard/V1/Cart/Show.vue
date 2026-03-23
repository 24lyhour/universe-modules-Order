<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { type VNode } from 'vue';
import {
    ArrowLeft,
    Pencil,
    Trash2,
    ShoppingCart,
    User,
    MapPin,
    Calendar,
    ArrowRightCircle,
    Package,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { toast } from '@/composables/useToast';
import type { CartItem } from '@order/types';

defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Carts', href: '/dashboard/carts' },
            { title: 'Cart Details', href: '#' },
        ]}, () => page),
});

const props = defineProps<{
    cart: CartItem;
}>();

const getStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        active: 'default',
        abandoned: 'outline',
        converted: 'secondary',
        expired: 'destructive',
    };
    return variants[status] || 'outline';
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
    router.visit(`/dashboard/carts/${props.cart.id}/edit`);
};

const handleBack = () => {
    router.visit('/dashboard/carts');
};

const handleConvertToOrder = () => {
    router.post(`/dashboard/carts/${props.cart.id}/convert-to-order`, {}, {
        onSuccess: () => {
            toast.success('Cart converted to order successfully.');
        },
    });
};

const truncateUuid = (uuid: string) => {
    return uuid.substring(0, 8) + '...';
};
</script>

<template>
    <Head :title="`Cart ${truncateUuid(cart.uuid)}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="handleBack">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight font-mono">{{ truncateUuid(cart.uuid) }}</h1>
                    <p class="text-muted-foreground">Cart details and items</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    v-if="cart.status === 'active' && (cart.items_count ?? 0) > 0"
                    variant="default"
                    @click="handleConvertToOrder"
                >
                    <ArrowRightCircle class="mr-2 h-4 w-4" />
                    Convert to Order
                </Button>
                <Button variant="outline" @click="handleEdit">
                    <Pencil class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Status Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ShoppingCart class="h-5 w-5" />
                            Cart Status
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-4">
                            <div>
                                <p class="text-sm text-muted-foreground">Status</p>
                                <Badge :variant="getStatusVariant(cart.status)" class="mt-1 capitalize">
                                    {{ cart.status }}
                                </Badge>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Active</p>
                                <Badge :variant="cart.is_active ? 'default' : 'outline'" class="mt-1">
                                    {{ cart.is_active ? 'Yes' : 'No' }}
                                </Badge>
                            </div>
                            <div v-if="cart.expires_at">
                                <p class="text-sm text-muted-foreground">Expires</p>
                                <p class="mt-1 text-sm font-medium">{{ formatDate(cart.expires_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cart Items -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Cart Items ({{ cart.items?.length || 0 }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="item in cart.items"
                                :key="item.id"
                                class="flex items-center justify-between rounded-lg border p-4"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-muted">
                                        <Package class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ item.product?.name || 'Product' }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            Qty: {{ item.quantity }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">{{ formatCurrency(item.total_amount) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatCurrency(item.unit_price) }} each
                                    </p>
                                </div>
                            </div>
                            <div v-if="!cart.items?.length" class="py-8 text-center text-muted-foreground">
                                No items in this cart.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card v-if="cart.notes">
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="whitespace-pre-wrap text-muted-foreground">{{ cart.notes }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Cart Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>Cart Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Items</span>
                            <span>{{ cart.items_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Total Qty</span>
                            <span>{{ cart.total_quantity ?? 0 }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span>{{ formatCurrency(cart.total_amount ?? 0) }}</span>
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
                        <div v-if="cart.customer">
                            <p class="font-medium">{{ cart.customer.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ cart.customer.email }}</p>
                        </div>
                        <p v-else class="text-muted-foreground">Guest</p>
                    </CardContent>
                </Card>

                <!-- Outlet Info -->
                <Card v-if="cart.outlet">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Outlet
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-medium">{{ cart.outlet.name }}</p>
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
                            <p class="text-sm font-medium">{{ formatDate(cart.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Updated</p>
                            <p class="text-sm font-medium">{{ formatDate(cart.updated_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
