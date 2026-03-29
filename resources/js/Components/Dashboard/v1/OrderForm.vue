<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import type { InertiaForm } from '@inertiajs/vue3';
import type { OrderFormData, CustomerInfo, OutletInfo, SelectOption } from '@order/types';
import { useTranslation } from '@/composables/useTranslation';



const { __ } = useTranslation();

interface Props {
    mode?: 'create' | 'edit';
    customers?: CustomerInfo[];
    outlets?: OutletInfo[];
    statuses?: SelectOption[];
    paymentStatuses?: SelectOption[];
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
    customers: () => [],
    outlets: () => [],
    statuses: () => [],
    paymentStatuses: () => [],
});

const model = defineModel<InertiaForm<OrderFormData>>({ required: true });

// Convert customer_id to string for Select component
const customerIdString = computed({
    get: () => model.value.customer_id?.toString() ?? 'none',
    set: (val: string) => {
        model.value.customer_id = val && val !== 'none' ? Number(val) : null;
    },
});

// Convert outlet_id to string for Select component
const outletIdString = computed({
    get: () => model.value.outlet_id?.toString() ?? 'none',
    set: (val: string) => {
        model.value.outlet_id = val && val !== 'none' ? Number(val) : null;
    },
});

// Calculate total when subtotal, discount, or tax changes
const calculateTotal = () => {
    model.value.total_amount = model.value.subtotal - model.value.discount_amount + model.value.tax_amount;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Customer & Outlet Section -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Customer & Location</h3>
                <p class="text-sm text-muted-foreground">
                    {{ mode === 'create' ? 'Select customer and outlet' : 'Update customer and outlet' }}
                </p>
            </div>
            <Separator />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="customer_id">Customer</Label>
                    <Select v-model="customerIdString">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select customer (optional)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">No Customer (Guest)</SelectItem>
                            <SelectItem
                                v-for="customer in props.customers"
                                :key="customer.id"
                                :value="customer.id.toString()"
                            >
                                {{ customer.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="model.errors.customer_id" class="text-sm text-destructive">
                        {{ model.errors.customer_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="outlet_id">Outlet</Label>
                    <Select v-model="outletIdString">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select outlet (optional)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">No Outlet</SelectItem>
                            <SelectItem
                                v-for="outlet in props.outlets"
                                :key="outlet.id"
                                :value="outlet.id.toString()"
                            >
                                {{ outlet.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="model.errors.outlet_id" class="text-sm text-destructive">
                        {{ model.errors.outlet_id }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Section -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Status</h3>
                <p class="text-sm text-muted-foreground">Order and payment status</p>
            </div>
            <Separator />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="status">Order Status</Label>
                    <Select v-model="model.status">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in props.statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="model.errors.status" class="text-sm text-destructive">
                        {{ model.errors.status }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="payment_status">Payment Status</Label>
                    <Select v-model="model.payment_status">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select payment status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in props.paymentStatuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="model.errors.payment_status" class="text-sm text-destructive">
                        {{ model.errors.payment_status }}
                    </p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="payment_method">Payment Method</Label>
                    <Input
                        id="payment_method"
                        v-model="model.payment_method"
                        type="text"
                        placeholder="e.g., Cash, Card, Bank Transfer"
                    />
                    <p v-if="model.errors.payment_method" class="text-sm text-destructive">
                        {{ model.errors.payment_method }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Amounts Section -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Amounts</h3>
                <p class="text-sm text-muted-foreground">Order pricing details</p>
            </div>
            <Separator />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="subtotal">Subtotal</Label>
                    <Input
                        id="subtotal"
                        v-model.number="model.subtotal"
                        type="number"
                        step="0.01"
                        min="0"
                        @change="calculateTotal"
                    />
                    <p v-if="model.errors.subtotal" class="text-sm text-destructive">
                        {{ model.errors.subtotal }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="discount_amount">Discount</Label>
                    <Input
                        id="discount_amount"
                        v-model.number="model.discount_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        @change="calculateTotal"
                    />
                    <p v-if="model.errors.discount_amount" class="text-sm text-destructive">
                        {{ model.errors.discount_amount }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="tax_amount">Tax</Label>
                    <Input
                        id="tax_amount"
                        v-model.number="model.tax_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        @change="calculateTotal"
                    />
                    <p v-if="model.errors.tax_amount" class="text-sm text-destructive">
                        {{ model.errors.tax_amount }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="total_amount">Total</Label>
                    <Input
                        id="total_amount"
                        v-model.number="model.total_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        readonly
                        class="bg-muted"
                    />
                    <p v-if="model.errors.total_amount" class="text-sm text-destructive">
                        {{ model.errors.total_amount }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">{{__("Notes")}}</h3>
                <p class="text-sm text-muted-foreground">{{__("Additional information")}}</p>
            </div>
            <Separator />

            <div class="space-y-2">
                <Label for="notes">{{__("Notes")}}</Label>
                <Textarea
                    id="notes"
                    v-model="model.notes"
                    placeholder="Order notes..."
                    rows="3"
                />
                <p v-if="model.errors.notes" class="text-sm text-destructive">
                    {{ model.errors.notes }}
                </p>
            </div>
        </div>
    </div>
</template>
