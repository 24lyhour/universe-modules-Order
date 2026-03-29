<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import type { InertiaForm } from '@inertiajs/vue3';
import type { CartFormData, CustomerInfo, OutletInfo, SelectOption } from '@order/types';
import { useTranslation } from '@/composables/useTranslation';

const {__} = useTranslation();

interface Props {
    mode?: 'create' | 'edit';
    customers?: CustomerInfo[];
    outlets?: OutletInfo[];
    statuses?: SelectOption[];
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
    customers: () => [],
    outlets: () => [],
    statuses: () => [],
});

const model = defineModel<InertiaForm<CartFormData>>({ required: true });

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

// Computed for Switch v-model
const isActive = computed({
    get: () => model.value.is_active === true,
    set: (value: boolean) => {
        model.value.is_active = value;
    },
});
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
                <p class="text-sm text-muted-foreground">Cart status and activity</p>
            </div>
            <Separator />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="status">Cart Status</Label>
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
                    <Label for="expires_at">Expires At</Label>
                    <Input
                        id="expires_at"
                        v-model="model.expires_at"
                        type="datetime-local"
                    />
                    <p v-if="model.errors.expires_at" class="text-sm text-destructive">
                        {{ model.errors.expires_at }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Options Section -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Options</h3>
                <p class="text-sm text-muted-foreground">Additional settings</p>
            </div>
            <Separator />

            <div class="space-y-2">
                <Label for="is_active">Status</Label>
                <div class="flex items-center space-x-2 pt-1">
                    <Switch id="is_active" v-model="isActive" />
                    <Label for="is_active" class="font-normal cursor-pointer">
                        {{ isActive ? 'Active' : 'Inactive' }}
                    </Label>
                </div>
                <p class="text-sm text-muted-foreground">
                    Enable this cart for use
                </p>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium">Notes</h3>
                <p class="text-sm text-muted-foreground">Additional information</p>
            </div>
            <Separator />

            <div class="space-y-2">
                <Label for="notes">{{__("Notes")}}</Label>
                <Textarea
                    id="notes"
                    v-model="model.notes"
                    placeholder="Cart notes..."
                    rows="3"
                />
                <p v-if="model.errors.notes" class="text-sm text-destructive">
                    {{ model.errors.notes }}
                </p>
            </div>
        </div>
    </div>
</template>
