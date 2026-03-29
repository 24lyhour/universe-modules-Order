<script setup lang="ts">
import { computed, type VNode } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useModal } from 'momentum-modal';
import AppLayout from '@/layouts/AppLayout.vue';
import OrderActionForm from '@order/Components/Dashboard/V1/OrderActionForm.vue';
import type { OrderActionData } from '@order/types';

const { show, close, redirect } = useModal();

const props = defineProps<{
    order: OrderActionData;
}>();

// Persistent layout - static breadcrumbs (cannot reference props in defineOptions due to hoisting)
defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Orders', href: '/dashboard/orders' },
            { title: 'Update Status', href: '#' },
        ]}, () => page),
});

const isOpen = computed({
    get: () => show.value,
    set: (val: boolean) => {
        if (!val) {
            close();
            redirect();
        }
    },
});

const handleSuccess = () => {
    close();
    redirect();
};
</script>

<template>
    <Head :title="`Update Status - ${order.order_number}`" />

    <OrderActionForm
        v-model:open="isOpen"
        :order="order"
        @success="handleSuccess"
    />
</template>
