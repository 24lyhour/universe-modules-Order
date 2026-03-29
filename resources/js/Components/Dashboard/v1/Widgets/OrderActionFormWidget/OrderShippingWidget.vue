<script setup lang="ts">
import { MapPin, Truck, Phone, User, Navigation, Calendar, DollarSign, Copy, Check, ExternalLink, Store, Route } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DeliveryRouteMap } from '@/components/shared';
import { toast } from 'vue-sonner';
import type { OrderShippingInfo, OutletInfo, CustomerInfo } from '@order/types';

const { __ } = useTranslation();

const props = defineProps<{
    shipping: OrderShippingInfo | null | undefined;
    outlet?: OutletInfo | null;
    customer?: CustomerInfo | null;
    status?: string;
}>();

// Statuses where shipping details should be shown
const shippingDetailStatuses = ['delivering', 'delivered', 'completed'];
const showShippingDetails = computed(() =>
    props.status && shippingDetailStatuses.includes(props.status)
);

// Check if we have outlet address
const hasOutletAddress = computed(() => !!props.outlet?.address);

// Check if we have shipping address or customer address as fallback
const hasShippingAddress = computed(() => !!props.shipping?.street_1);
const hasCustomerAddress = computed(() => !!props.customer?.address);
const hasToAddress = computed(() => hasShippingAddress.value || hasCustomerAddress.value);

const copiedTracking = ref(false);
const copiedPhone = ref(false);

// Format currency
const formatCurrency = (amount: number | undefined) => {
    if (amount === undefined || amount === null) return null;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

// Format date
const formatDate = (date: string | null | undefined) => {
    if (!date) return null;
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Format coordinate (handle string or number)
const formatCoordinate = (coord: number | string | null | undefined): string => {
    if (coord === null || coord === undefined) return '0.000000';
    const num = typeof coord === 'string' ? parseFloat(coord) : coord;
    return isNaN(num) ? '0.000000' : num.toFixed(6);
};

// Open Google Maps with directions from outlet to customer
const openMapsDirections = (fromLat: number | string, fromLng: number | string, toLat: number | string, toLng: number | string) => {
    const fromLatNum = typeof fromLat === 'string' ? parseFloat(fromLat) : fromLat;
    const fromLngNum = typeof fromLng === 'string' ? parseFloat(fromLng) : fromLng;
    const toLatNum   = typeof toLat === 'string' ? parseFloat(toLat) : toLat;
    const toLngNum   = typeof toLng === 'string' ? parseFloat(toLng) : toLng;
    window.open(`https://www.google.com/maps/dir/${fromLatNum},${fromLngNum}/${toLatNum},${toLngNum}`, '_blank');
};

// Copy to clipboard
const copyToClipboard = async (text: string, type: 'tracking' | 'phone') => {
    try {
        await navigator.clipboard.writeText(text);
        if (type === 'tracking') {
            copiedTracking.value = true;
            setTimeout(() => copiedTracking.value = false, 2000);
        } else {
            copiedPhone.value = true;
            setTimeout(() => copiedPhone.value = false, 2000);
        }
        toast.success(__('Copied to clipboard'));
    } catch {
        toast.error(__('Failed to copy'));
    }
};

// Check if outlet has coordinates
const hasOutletCoords = computed(() => !!props.outlet?.latitude && !!props.outlet?.longitude);

// Check if shipping has coordinates
const hasShippingCoords = computed(() => !!props.shipping?.latitude && !!props.shipping?.longitude);

// Check if both have coordinates (for route line)
const hasBothCoords = computed(() => hasOutletCoords.value && hasShippingCoords.value);
</script>

<template>
                <!-- Delivery Route Map (FROM outlet → TO customer) -->
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
                <Route class="h-4 w-4 text-primary" />
                {{ __('Shipping Route') }}
            </CardTitle>
        </CardHeader>
        <CardContent v-if="shipping || outlet" class="space-y-4">

            <div v-if="(outlet?.latitude && outlet?.longitude) || (shipping?.latitude && shipping?.longitude)" class="space-y-2">
                <div class="flex items-center gap-2">
                    <Route class="h-4 w-4 text-muted-foreground" />
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Delivery Route') }}</p>
                </div>
                <DeliveryRouteMap
                    height="500px"
                    :from-latitude="outlet?.latitude ? Number(outlet.latitude) : null"
                    :from-longitude="outlet?.longitude ? Number(outlet.longitude) : null"
                    :from-label="outlet?.name || __('Outlet')"
                    :to-latitude="shipping?.latitude ? Number(shipping.latitude) : null"
                    :to-longitude="shipping?.longitude ? Number(shipping.longitude) : null"
                    :to-label="shipping?.recipient_name || __('Customer')"
                />
                <!-- Legend -->
                <div class="flex items-center justify-center gap-4 text-xs text-muted-foreground">
                    <span v-if="hasOutletCoords" class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                        {{ __('Outlet') }}
                    </span>
                    <span v-if="hasBothCoords" class="flex items-center gap-1.5">
                        <span class="w-2 h-0.5 bg-indigo-500 rounded"></span>
                        {{ __('Route') }}
                    </span>
                    <span v-if="hasShippingCoords" class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        {{ __('Customer') }}
                    </span>
                </div>
                <!-- View Map Button -->
                <div v-if="hasBothCoords" class="flex justify-center pt-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="gap-2"
                        @click="openMapsDirections(outlet!.latitude!, outlet!.longitude!, shipping!.latitude!, shipping!.longitude!)"
                    >
                        <Navigation class="h-4 w-4" />
                        {{ __('View Map') }}
                    </Button>
                </div>
            </div>

            <!-- Recipient Info -->
            <div v-if="shipping?.recipient_name || shipping?.phone" class="space-y-2">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Recipient') }}</p>
                <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                        <User class="h-5 w-5 text-primary" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p v-if="shipping.recipient_name" class="font-semibold">{{ shipping.recipient_name }}</p>
                        <div v-if="shipping.phone" class="flex items-center gap-2 mt-1">
                            <Phone class="h-3 w-3 text-muted-foreground" />
                            <span class="text-sm font-mono">{{ shipping.phone }}</span>
                            <div class="flex items-center gap-1 ml-auto">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-6 w-6"
                                                @click="copyToClipboard(shipping.phone!, 'phone')"
                                            >
                                                <Check v-if="copiedPhone" class="h-3 w-3 text-green-500" />
                                                <Copy v-else class="h-3 w-3" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Copy') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-6 w-6"
                                                as="a"
                                                :href="`tel:${shipping.phone}`"
                                            >
                                                <ExternalLink class="h-3 w-3" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Call') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carrier & Tracking (only show when delivering or later) -->
            <div v-if="showShippingDetails && (shipping?.carrier || shipping?.tracking_number || shipping?.method)" class="space-y-2">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Carrier & Tracking') }}</p>
                <div class="p-3 rounded-lg bg-purple-50/50 dark:bg-purple-950/20 border border-purple-200/50 dark:border-purple-800/30">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center shrink-0">
                            <Truck class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <Badge v-if="shipping?.method" variant="secondary" class="capitalize">
                                    {{ shipping.method.replace('_', ' ') }}
                                </Badge>
                                <span v-if="shipping?.carrier" class="font-semibold text-purple-700 dark:text-purple-300">{{ shipping.carrier }}</span>
                            </div>
                            <div v-if="shipping?.tracking_number" class="flex items-center gap-2 p-2 rounded bg-white/50 dark:bg-black/20">
                                <span class="text-xs text-muted-foreground">{{ __('Tracking #') }}:</span>
                                <span class="font-mono text-sm font-medium">{{ shipping.tracking_number }}</span>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-6 w-6 ml-auto"
                                                @click="copyToClipboard(shipping.tracking_number!, 'tracking')"
                                            >
                                                <Check v-if="copiedTracking" class="h-3 w-3 text-green-500" />
                                                <Copy v-else class="h-3 w-3" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Copy tracking number') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Cost & Estimated Delivery (only show when delivering or later) -->
            <div v-if="showShippingDetails && (shipping?.shipping_cost || shipping?.estimated_delivery_at)" class="grid grid-cols-2 gap-3">
                <div v-if="formatCurrency(shipping?.shipping_cost)" class="p-3 rounded-lg bg-muted/50 text-center">
                    <DollarSign class="h-5 w-5 mx-auto mb-1 text-muted-foreground" />
                    <p class="text-xs text-muted-foreground">{{ __('Shipping Cost') }}</p>
                    <p class="font-bold text-lg">{{ formatCurrency(shipping?.shipping_cost) }}</p>
                </div>
                <div v-if="formatDate(shipping?.estimated_delivery_at)" class="p-3 rounded-lg bg-muted/50 text-center">
                    <Calendar class="h-5 w-5 mx-auto mb-1 text-muted-foreground" />
                    <p class="text-xs text-muted-foreground">{{ __('Est. Delivery') }}</p>
                    <p class="font-semibold text-sm">{{ formatDate(shipping?.estimated_delivery_at) }}</p>
                </div>
            </div>
        </CardContent>

        <!-- No Shipping Info -->
        <CardContent v-else class="py-8">
            <div class="text-center">
                <Route class="h-10 w-10 mx-auto mb-3 text-muted-foreground/40" />
                <p class="text-muted-foreground font-medium">{{ __('No shipping information') }}</p>
                <p class="text-xs text-muted-foreground/70 mt-1">{{ __('Shipping route will appear once order is ready for delivery') }}</p>
            </div>
        </CardContent>
    </Card>
</template>
