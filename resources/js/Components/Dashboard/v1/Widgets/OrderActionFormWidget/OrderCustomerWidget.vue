<script setup lang="ts">
import { User, Store, Mail, Phone, ExternalLink, Copy, Check, FileText, Clock, CalendarDays, Truck, PackageCheck, XCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { toast } from 'vue-sonner';

const { __ } = useTranslation();

interface Customer {
    id: number;
    name: string;
    email?: string;
    phone?: string;
}

interface Outlet {
    id: number;
    name: string;
    address?: string | null;
}

const props = defineProps<{
    customer: Customer | null | undefined;
    outlet: Outlet | null | undefined;
    notes?: string | null;
    // Timeline props
    createdAt?: string;
    shippedAt?: string | null;
    deliveredAt?: string | null;
    cancelledAt?: string | null;
    completedAt?: string | null;
}>();

const copiedEmail = ref(false);
const copiedPhone = ref(false);

const copyToClipboard = async (text: string, type: 'email' | 'phone') => {
    try {
        await navigator.clipboard.writeText(text);
        if (type === 'email') {
            copiedEmail.value = true;
            setTimeout(() => copiedEmail.value = false, 2000);
        } else {
            copiedPhone.value = true;
            setTimeout(() => copiedPhone.value = false, 2000);
        }
        toast.success(__('Copied to clipboard'));
    } catch {
        toast.error(__('Failed to copy'));
    }
};

// Format date for timeline
const formatDate = (date: string | null | undefined) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Check if there's any timeline data
const hasTimelineData = computed(() => {
    return props.createdAt || props.shippedAt || props.deliveredAt || props.completedAt || props.cancelledAt;
});
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="text-sm font-medium flex items-center gap-2">
                <User class="h-4 w-4 text-primary" />
                {{ __('Customer & Outlet') }}
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Customer Section -->
            <div class="space-y-2">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Customer') }}</p>
                <div v-if="customer" class="space-y-3">
                    <!-- Customer Name & Avatar -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-linear-to-br from-primary/20 to-primary/10 flex items-center justify-center text-sm font-bold text-primary border border-primary/20">
                            {{ customer.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-semibold">{{ customer.name }}</p>
                            <p class="text-xs text-muted-foreground">ID: #{{ customer.id }}</p>
                        </div>
                    </div>

                    <!-- Contact Actions -->
                    <div class="space-y-2">
                        <!-- Email -->
                        <div v-if="customer.email" class="flex items-center justify-between gap-2 p-2 rounded-lg bg-muted/50 group">
                            <div class="flex items-center gap-2 min-w-0">
                                <Mail class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="text-sm truncate">{{ customer.email }}</span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7"
                                                @click="copyToClipboard(customer.email!, 'email')"
                                            >
                                                <Check v-if="copiedEmail" class="h-3.5 w-3.5 text-green-500" />
                                                <Copy v-else class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Copy email') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7"
                                                as="a"
                                                :href="`mailto:${customer.email}`"
                                            >
                                                <ExternalLink class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Send email') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div v-if="customer.phone" class="flex items-center justify-between gap-2 p-2 rounded-lg bg-muted/50 group">
                            <div class="flex items-center gap-2 min-w-0">
                                <Phone class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="text-sm font-mono">{{ customer.phone }}</span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7"
                                                @click="copyToClipboard(customer.phone!, 'phone')"
                                            >
                                                <Check v-if="copiedPhone" class="h-3.5 w-3.5 text-green-500" />
                                                <Copy v-else class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Copy phone') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7"
                                                as="a"
                                                :href="`tel:${customer.phone}`"
                                            >
                                                <ExternalLink class="h-3.5 w-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>{{ __('Call customer') }}</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>

                        <!-- No contact info -->
                        <p v-if="!customer.email && !customer.phone" class="text-xs text-muted-foreground italic">
                            {{ __('No contact information available') }}
                        </p>
                    </div>
                </div>
                <div v-else class="p-4 border rounded-lg border-dashed text-center">
                    <User class="h-8 w-8 mx-auto mb-2 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground font-medium">{{ __('Guest Order') }}</p>
                    <p class="text-xs text-muted-foreground/70">{{ __('No customer account linked') }}</p>
                </div>
            </div>

            <!-- Outlet Section -->
            <div class="space-y-2 pt-2 border-t">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ __('Outlet') }}</p>
                <div v-if="outlet" class="flex items-center gap-3 p-3 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/50 dark:border-emerald-800/30">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0">
                        <Store class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-emerald-700 dark:text-emerald-300">{{ outlet.name }}</p>
                        <p v-if="outlet.address" class="text-xs text-muted-foreground truncate">{{ outlet.address }}</p>
                        <p v-else class="text-xs text-muted-foreground">ID: #{{ outlet.id }}</p>
                    </div>
                </div>
                <div v-else class="p-4 border rounded-lg border-dashed text-center">
                    <Store class="h-8 w-8 mx-auto mb-2 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground font-medium">{{ __('No outlet assigned') }}</p>
                </div>
            </div>

            <!-- Notes Section -->
            <div v-if="notes" class="space-y-2 pt-2 border-t">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                    <FileText class="h-3 w-3" />
                    {{ __('Order Notes') }}
                </p>
                <div class="p-3 rounded-lg bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/30">
                    <p class="text-sm text-amber-800 dark:text-amber-200 whitespace-pre-wrap">{{ notes }}</p>
                </div>
            </div>

            <!-- Timeline Section -->
            <div v-if="hasTimelineData" class="space-y-2 pt-2 border-t">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                    <Clock class="h-3 w-3" />
                    {{ __('Timeline') }}
                </p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div v-if="createdAt" class="flex items-center gap-2 p-2 rounded-lg bg-muted/50">
                        <CalendarDays class="h-4 w-4 text-blue-500 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ __('Created') }}</p>
                            <p class="font-medium truncate text-xs">{{ formatDate(createdAt) }}</p>
                        </div>
                    </div>

                    <div v-if="shippedAt" class="flex items-center gap-2 p-2 rounded-lg bg-muted/50">
                        <Truck class="h-4 w-4 text-purple-500 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ __('Shipped') }}</p>
                            <p class="font-medium truncate text-xs">{{ formatDate(shippedAt) }}</p>
                        </div>
                    </div>

                    <div v-if="deliveredAt" class="flex items-center gap-2 p-2 rounded-lg bg-muted/50">
                        <PackageCheck class="h-4 w-4 text-green-500 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">{{ __('Delivered') }}</p>
                            <p class="font-medium truncate text-xs">{{ formatDate(deliveredAt) }}</p>
                        </div>
                    </div>

                    <div v-if="completedAt" class="flex items-center gap-2 p-2 rounded-lg bg-green-50 dark:bg-green-900/20">
                        <PackageCheck class="h-4 w-4 text-green-600 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs text-green-600">{{ __('Completed') }}</p>
                            <p class="font-medium truncate text-xs text-green-700 dark:text-green-400">{{ formatDate(completedAt) }}</p>
                        </div>
                    </div>

                    <div v-if="cancelledAt" class="flex items-center gap-2 p-2 rounded-lg bg-red-50 dark:bg-red-900/20">
                        <XCircle class="h-4 w-4 text-red-500 shrink-0" />
                        <div class="min-w-0">
                            <p class="text-xs text-red-600">{{ __('Cancelled') }}</p>
                            <p class="font-medium truncate text-xs text-red-700 dark:text-red-400">{{ formatDate(cancelledAt) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
