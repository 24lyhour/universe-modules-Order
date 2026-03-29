<script setup lang="ts">
import { MapPin, Truck, Phone, User, Navigation, Calendar, DollarSign, Copy, Check, ExternalLink, Store, Route } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ShippingRouteMap } from '@/components/shared';
import { toast } from 'vue-sonner';
import type { OrderShippingInfo, OutletInfo } from '@order/types';

const { __ } = useTranslation();

const props = defineProps<{
    shipping: OrderShippingInfo | null | undefined;
    outlet?: OutletInfo | null;
}>();

// Check if we have outlet address
const hasOutletAddress = computed(() => !!props.outlet?.address);

// Check if we have shipping address
const hasShippingAddress = computed(() => !!props.shipping?.street_1);

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

// Open Google Maps with coordinates
const openMaps = (lat: number | string, lng: number | string) => {
    const latNum = typeof lat === 'string' ? parseFloat(lat) : lat;
    const lngNum = typeof lng === 'string' ? parseFloat(lng) : lng;
    window.open(`https://www.google.com/maps?q=${latNum},${lngNum}`, '_blank');
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

// Build full address string
const getFullAddress = (shipping: OrderShippingInfo) => {
    const parts = [
        shipping.street_1,
        shipping.street_2,
        [shipping.city, shipping.state, shipping.postal_code].filter(Boolean).join(', '),
        shipping.country,
    ].filter(Boolean);
    return parts.join('\n');
};
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
                <Route class="h-4 w-4 text-primary" />
                {{ __('Shipping Route') }}
            </CardTitle>
        </CardHeader>
        <CardContent v-if="shipping || outlet" class="space-y-4">
            <!-- FROM: Outlet Address -->
            <div v-if="outlet" class="space-y-2">
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300 border-orange-300 dark:border-orange-700 text-xs">
                        {{ __('FROM') }}
                    </Badge>
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Outlet') }}</p>
                </div>
                <div class="p-3 rounded-lg border bg-orange-50/50 dark:bg-orange-950/20 border-orange-200/50 dark:border-orange-800/30">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center shrink-0 mt-0.5">
                            <Store class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div class="flex-1 text-sm space-y-1">
                            <p class="font-semibold text-orange-900 dark:text-orange-100">{{ outlet.name }}</p>
                            <p v-if="outlet.address" class="text-orange-700/80 dark:text-orange-300/80">{{ outlet.address }}</p>
                            <div v-if="outlet.phone" class="flex items-center gap-2 mt-1">
                                <Phone class="h-3 w-3 text-orange-600/70 dark:text-orange-400/70" />
                                <span class="text-xs font-mono text-orange-700/70 dark:text-orange-300/70">{{ outlet.phone }}</span>
                            </div>
                            <!-- Open in Maps button for outlet -->
                            <div v-if="outlet.google_map_url" class="mt-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 text-xs gap-1.5 border-orange-300 dark:border-orange-700 hover:bg-orange-100 dark:hover:bg-orange-900/50"
                                    as="a"
                                    :href="outlet.google_map_url"
                                    target="_blank"
                                >
                                    <Navigation class="h-3 w-3" />
                                    {{ __('View on Maps') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Arrow indicator between FROM and TO -->
            <div v-if="hasOutletAddress && hasShippingAddress" class="flex justify-center py-1">
                <div class="flex flex-col items-center gap-0.5 text-muted-foreground">
                    <div class="w-0.5 h-4 bg-gradient-to-b from-orange-400 to-blue-400 rounded-full" />
                    <Truck class="h-4 w-4 text-primary animate-pulse" />
                    <div class="w-0.5 h-4 bg-gradient-to-b from-blue-400 to-blue-500 rounded-full" />
                </div>
            </div>

            <!-- TO: Customer Delivery Address -->
            <div v-if="shipping?.street_1" class="space-y-2">
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 border-blue-300 dark:border-blue-700 text-xs">
                        {{ __('TO') }}
                    </Badge>
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Customer') }}</p>
                </div>
                <div class="p-3 rounded-lg border bg-blue-50/50 dark:bg-blue-950/20 border-blue-200/50 dark:border-blue-800/30">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center shrink-0 mt-0.5">
                            <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="flex-1 text-sm space-y-0.5">
                            <p class="font-medium text-blue-900 dark:text-blue-100">{{ shipping.street_1 }}</p>
                            <p v-if="shipping.street_2" class="text-blue-700/80 dark:text-blue-300/80">{{ shipping.street_2 }}</p>
                            <p class="text-blue-700/70 dark:text-blue-300/70">
                                {{ [shipping.city, shipping.state, shipping.postal_code].filter(Boolean).join(', ') }}
                            </p>
                            <p v-if="shipping.country" class="text-blue-700/70 dark:text-blue-300/70 font-medium">{{ shipping.country }}</p>
                        </div>
                    </div>

                    <!-- GPS & Map Link -->
                    <div v-if="shipping.latitude && shipping.longitude" class="mt-3 pt-3 border-t border-blue-200/50 dark:border-blue-800/30">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <Navigation class="h-4 w-4 text-green-600 dark:text-green-400" />
                                <span class="font-mono text-xs text-muted-foreground">
                                    {{ formatCoordinate(shipping.latitude) }}, {{ formatCoordinate(shipping.longitude) }}
                                </span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-7 text-xs gap-1.5"
                                @click="openMaps(shipping.latitude!, shipping.longitude!)"
                            >
                                <Navigation class="h-3 w-3" />
                                {{ __('Open in Maps') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Embedded Map Display -->
            <div v-if="shipping?.latitude && shipping?.longitude" class="space-y-2">
                <div class="flex items-center gap-2">
                    <MapPin class="h-4 w-4 text-muted-foreground" />
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Delivery Location') }}</p>
                </div>
                <ShippingRouteMap
                    height="180px"
                    :to-latitude="Number(shipping.latitude)"
                    :to-longitude="Number(shipping.longitude)"
                    :to-label="shipping.recipient_name || __('Customer')"
                />
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

            <!-- Carrier & Tracking -->
            <div v-if="shipping?.carrier || shipping?.tracking_number || shipping?.method" class="space-y-2">
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

            <!-- Shipping Cost & Estimated Delivery -->
            <div v-if="shipping?.shipping_cost || shipping?.estimated_delivery_at" class="grid grid-cols-2 gap-3">
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
