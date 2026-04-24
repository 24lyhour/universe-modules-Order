<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, type VNode } from 'vue';
import { X, Search, ChevronLeft, ChevronRight } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast } from '@/composables/useToast';
import { useTranslation } from '@/composables/useTranslation';
import { ModalConfirm } from '@/components/shared';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ReviewStatsWidget, ReviewCard } from '@order/Components/Dashboard/v1/Widgets/ReplyFormWidget';

const { __ } = useTranslation();

interface ProductReviewItem {
    id: number;
    uuid?: string;
    customer_id?: number;
    product_id?: number;
    rating: number;
    comment: string | null;
    reply: string | null;
    is_active: boolean;
    is_verified?: boolean;
    helpful_count?: number;
    created_at: string;
    customer?: { id: number; name: string; avatar?: string; total_spend?: number; total_reviews?: number };
    product?: { id: number; name: string; sku?: string };
    order?: { id: number; order_number: string };
    formatted_date: string;
    has_reply: boolean;
}

interface Props {
    reviewItems: {
        data: ProductReviewItem[];
        meta: { current_page: number; last_page: number; per_page: number; total: number };
    };
    filters: Record<string, string>;
    products: Array<{ id: number; name: string }>;
    stats: {
        total: number;
        active: number;
        pending_reply: number;
        average_rating: number;
        '5_star': number;
        '4_star': number;
        '3_star': number;
        '2_star': number;
        '1_star': number;
    };
}

defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Product Reviews', href: '/dashboard/product-reviews' },
        ]}, () => page),
});

const props = defineProps<Props>();

// State
const searchQuery = ref(props.filters.search || '');
const ratingFilter = ref(props.filters.rating || 'all');
const productFilter = ref(props.filters.product_id || 'all');
const activeFilter = ref(props.filters.is_active || 'all');
const replyFilter = ref(props.filters.has_reply || 'all');
const perPage = ref(props.reviewItems.meta.per_page);
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);
const selectedItem = ref<ProductReviewItem | null>(null);

const pagination = computed(() => ({
    current_page: props.reviewItems.meta.current_page,
    last_page: props.reviewItems.meta.last_page,
    per_page: props.reviewItems.meta.per_page,
    total: props.reviewItems.meta.total,
}));

const hasActiveFilters = computed(() => {
    return !!(searchQuery.value || ratingFilter.value !== 'all' || productFilter.value !== 'all' || activeFilter.value !== 'all' || replyFilter.value !== 'all');
});

const applyFilters = (overrides: { page?: number; per_page?: number } = {}) => {
    router.get('/dashboard/product-reviews', {
        search: searchQuery.value || undefined,
        rating: ratingFilter.value !== 'all' ? ratingFilter.value : undefined,
        product_id: productFilter.value !== 'all' ? productFilter.value : undefined,
        is_active: activeFilter.value !== 'all' ? activeFilter.value : undefined,
        has_reply: replyFilter.value !== 'all' ? replyFilter.value : undefined,
        per_page: perPage.value,
        ...overrides,
    }, { preserveState: true });
};

const handlePageChange = (page: number) => applyFilters({ page });
const handleSearch = () => applyFilters({ page: 1 });

const handleFilterChange = (type: string) => (value: string | number | boolean | bigint | Record<string, unknown> | null | undefined) => {
    if (type === 'rating') ratingFilter.value = String(value || 'all');
    if (type === 'product') productFilter.value = String(value || 'all');
    if (type === 'active') activeFilter.value = String(value || 'all');
    if (type === 'reply') replyFilter.value = String(value || 'all');
    if (type === 'perPage') perPage.value = Number(value) || 10;
    applyFilters({ page: 1 });
};

const handleClearFilters = () => {
    searchQuery.value = '';
    ratingFilter.value = 'all';
    productFilter.value = 'all';
    activeFilter.value = 'all';
    replyFilter.value = 'all';
    router.get('/dashboard/product-reviews', { per_page: perPage.value }, { preserveState: true });
};

const handleView = (review: { id: number }) => router.visit(`/dashboard/product-reviews/${review.id}`);
const handleEdit = (review: { id: number }) => router.visit(`/dashboard/product-reviews/${review.id}/edit`);
const handleReply = (review: { id: number }) => router.visit(`/dashboard/product-reviews/${review.id}/reply`);
const handleDelete = () => {
    if (!selectedItem.value) return;
    isDeleting.value = true;
    router.delete(`/dashboard/product-reviews/${selectedItem.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            selectedItem.value = null;
            toast.success(__('Review deleted successfully.'));
        },
        onFinish: () => { isDeleting.value = false; },
    });
};

const openDeleteModal = (review: ProductReviewItem) => {
    selectedItem.value = review as ProductReviewItem;
    isDeleteModalOpen.value = true;
};
</script>

<template>
    <Head :title="__('Product Reviews')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ __('Product Reviews') }}</h1>
                <p class="text-muted-foreground">{{ __('Manage customer product reviews') }}</p>
            </div>
        </div>

        <!-- Stats Widget -->
        <ReviewStatsWidget :stats="stats" />

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    :placeholder="__('Search reviews...')"
                    class="pl-9"
                    @keyup.enter="handleSearch"
                />
            </div>

            <Select :model-value="ratingFilter" @update:model-value="handleFilterChange('rating')">
                <SelectTrigger class="w-[150px]"><SelectValue :placeholder="__('Rating')" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{ __('All Ratings') }}</SelectItem>
                    <SelectItem v-for="i in 5" :key="i" :value="String(i)">{{ i }} {{ __('Star') }}</SelectItem>
                </SelectContent>
            </Select>

            <Select :model-value="productFilter" @update:model-value="handleFilterChange('product')">
                <SelectTrigger class="w-[200px]"><SelectValue :placeholder="__('Product')" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{ __('All Products') }}</SelectItem>
                    <SelectItem v-for="product in products" :key="product.id" :value="String(product.id)">{{ product.name }}</SelectItem>
                </SelectContent>
            </Select>

            <Select :model-value="activeFilter" @update:model-value="handleFilterChange('active')">
                <SelectTrigger class="w-[150px]"><SelectValue :placeholder="__('Status')" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{ __('All Status') }}</SelectItem>
                    <SelectItem value="true">{{ __('Active') }}</SelectItem>
                    <SelectItem value="false">{{ __('Inactive') }}</SelectItem>
                </SelectContent>
            </Select>

            <Select :model-value="replyFilter" @update:model-value="handleFilterChange('reply')">
                <SelectTrigger class="w-[150px]"><SelectValue :placeholder="__('Reply')" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{ __('All') }}</SelectItem>
                    <SelectItem value="true">{{ __('Has Reply') }}</SelectItem>
                    <SelectItem value="false">{{ __('No Reply') }}</SelectItem>
                </SelectContent>
            </Select>

            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="handleClearFilters">
                <X class="mr-1 h-4 w-4" />
                {{ __('Clear') }}
            </Button>
        </div>

        <!-- Review Cards -->
        <div class="space-y-4">
            <div v-if="reviewItems.data.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <p class="text-lg font-medium text-muted-foreground">{{ __('No reviews found') }}</p>
                <p class="text-sm text-muted-foreground">{{ __('Try adjusting your filters') }}</p>
            </div>

            <ReviewCard
                v-for="review in reviewItems.data"
                :key="review.id"
                :review="review"
                show-product
                @view="handleView"
                @edit="handleEdit"
                @reply="handleReply"
                @delete="openDeleteModal"
            />
        </div>

        <!-- Pagination -->
        <div v-if="reviewItems.data.length > 0" class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span>{{ __('Show') }}</span>
                <Select :model-value="String(perPage)" @update:model-value="handleFilterChange('perPage')">
                    <SelectTrigger class="w-[80px]"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="20">20</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
                <span>{{ __('of') }} {{ pagination.total }} {{ __('reviews') }}</span>
            </div>

            <div v-if="pagination.last_page > 1" class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="pagination.current_page === 1"
                    @click="handlePageChange(pagination.current_page - 1)"
                >
                    <ChevronLeft class="h-4 w-4 mr-1" />
                    {{ __('Previous') }}
                </Button>
                <span class="text-sm text-muted-foreground">
                    {{ __('Page') }} {{ pagination.current_page }} {{ __('of') }} {{ pagination.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="handlePageChange(pagination.current_page + 1)"
                >
                    {{ __('Next') }}
                    <ChevronRight class="h-4 w-4 ml-1" />
                </Button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ModalConfirm
        v-model:open="isDeleteModalOpen"
        :title="__('Delete Review')"
        :description="__('Are you sure you want to delete this review? This action cannot be undone.')"
        :confirm-text="__('Delete')"
        variant="danger"
        :loading="isDeleting"
        @confirm="handleDelete"
    />
</template>
