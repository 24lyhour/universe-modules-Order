<script setup lang="ts">
import { computed } from 'vue';
import { Star, User, Package, Store, MessageSquare, Utensils, Truck, HeartHandshake } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useTranslation } from '@/composables/useTranslation';
import TiptapEditor from '@/components/TiptapEditor.vue';

const { __ } = useTranslation();

interface BaseReview {
    id: number;
    uuid?: string;
    comment: string | null;
    reply: string | null;
    replied_at: string | null;
    is_active: boolean;
    is_verified?: boolean;
    created_at: string;
    formatted_date: string;
    customer?: { id: number; name: string; avatar?: string };
}

interface ProductReviewData extends BaseReview {
    rating: number;
    product?: { id: number; name: string; sku?: string };
}

interface OutletReviewData extends BaseReview {
    overall_rating: number;
    food_rating?: number | null;
    service_rating?: number | null;
    delivery_rating?: number | null;
    packaging_rating?: number | null;
    tags?: string[];
    tag_labels?: string[];
    outlet?: { id: number; name: string };
}

type ReviewData = ProductReviewData | OutletReviewData;

const props = defineProps<{
    review: ReviewData;
    reply: string;
    errors?: Record<string, string>;
    maxLength?: number;
    type?: 'product' | 'outlet';
}>();

const emit = defineEmits<{
    'update:reply': [value: string];
}>();

const maxLength = computed(() => props.maxLength || 500);
const charCount = computed(() => props.reply.length);
const isOverLimit = computed(() => charCount.value > maxLength.value);

const isProductReview = computed(() => props.type === 'product' || 'rating' in props.review);
const isOutletReview = computed(() => props.type === 'outlet' || 'overall_rating' in props.review);

const rating = computed(() => {
    if ('rating' in props.review) return props.review.rating;
    if ('overall_rating' in props.review) return props.review.overall_rating;
    return 0;
});

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const renderStars = (r: number | null | undefined) => {
    if (!r) return '-';
    return '★'.repeat(r) + '☆'.repeat(5 - r);
};

const formatDate = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const ratingCategories = [
    { key: 'food_rating', label: 'Food', icon: Utensils, color: 'text-orange-500' },
    { key: 'service_rating', label: 'Service', icon: HeartHandshake, color: 'text-pink-500' },
    { key: 'delivery_rating', label: 'Delivery', icon: Truck, color: 'text-green-500' },
    { key: 'packaging_rating', label: 'Packaging', icon: Package, color: 'text-blue-500' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Review Summary Card -->
        <Card>
            <CardContent class="pt-4 space-y-4">
                <!-- Customer Info -->
                <div class="flex items-center gap-3">
                    <Avatar class="h-12 w-12">
                        <AvatarImage v-if="review.customer?.avatar" :src="review.customer.avatar" />
                        <AvatarFallback>
                            {{ review.customer?.name ? getInitials(review.customer.name) : '?' }}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <h4 class="font-semibold">{{ review.customer?.name || __('Guest') }}</h4>
                        <p class="text-sm text-muted-foreground">{{ review.formatted_date }}</p>
                    </div>
                </div>

                <!-- Product/Outlet Info -->
                <div class="flex items-center gap-2">
                    <template v-if="isProductReview && 'product' in review && review.product">
                        <Package class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm">{{ review.product.name }}</span>
                        <Badge v-if="review.product.sku" variant="outline" class="text-xs">{{ review.product.sku }}</Badge>
                    </template>
                    <template v-if="isOutletReview && 'outlet' in review && review.outlet">
                        <Store class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm">{{ review.outlet.name }}</span>
                    </template>
                </div>

                <!-- Overall Rating -->
                <div class="flex items-center gap-2">
                    <Star class="h-4 w-4 text-yellow-500" />
                    <span class="text-xl text-yellow-500">{{ renderStars(rating) }}</span>
                    <Badge variant="secondary">{{ rating }}/5</Badge>
                </div>

                <!-- Category Ratings (Outlet Only) -->
                <div v-if="isOutletReview && 'food_rating' in review" class="grid grid-cols-2 gap-2">
                    <div
                        v-for="cat in ratingCategories"
                        :key="cat.key"
                        class="flex items-center justify-between p-2 bg-muted rounded text-sm"
                    >
                        <div class="flex items-center gap-1">
                            <component :is="cat.icon" :class="['h-3 w-3', cat.color]" />
                            <span>{{ __(cat.label) }}</span>
                        </div>
                        <span class="text-yellow-500 text-xs">
                            {{ renderStars((review as OutletReviewData)[cat.key as keyof OutletReviewData] as number | null) }}
                        </span>
                    </div>
                </div>

                <!-- Tags (Outlet Only) -->
                <div v-if="isOutletReview && 'tag_labels' in review && (review as OutletReviewData).tag_labels?.length" class="flex flex-wrap gap-1">
                    <Badge v-for="tag in (review as OutletReviewData).tag_labels" :key="tag" variant="outline" class="text-xs">
                        {{ tag }}
                    </Badge>
                </div>

                <!-- Comment -->
                <div v-if="review.comment" class="p-3 bg-muted rounded-lg">
                    <p class="text-sm whitespace-pre-wrap">{{ review.comment }}</p>
                </div>
                <p v-else class="text-sm text-muted-foreground italic">{{ __('No comment provided') }}</p>
            </CardContent>
        </Card>

        <!-- Previous Reply Info (if editing) -->
        <div v-if="review.reply && review.replied_at" class="flex items-center gap-2 text-xs text-muted-foreground">
            <MessageSquare class="h-3 w-3" />
            <span>{{ __('Last replied on') }} {{ formatDate(review.replied_at) }}</span>
        </div>

        <!-- Reply Form -->
        <div class="space-y-2">
            <Label for="reply">{{ __('Your Reply') }}</Label>
            <TiptapEditor
                id="reply"
                :model-value="reply"
                @update:model-value="emit('update:reply', $event)"
                :placeholder="__('Write your reply to the customer...')"
                rows="5"
                :class="{ 'border-destructive': isOverLimit || errors?.reply }"
            />
            <div class="flex justify-between text-xs">
                <span v-if="errors?.reply" class="text-destructive">
                    {{ errors.reply }}
                </span>
                <span v-else class="text-muted-foreground">
                    {{ __('A thoughtful reply can improve customer satisfaction') }}
                </span>
                <span :class="isOverLimit ? 'text-destructive' : 'text-muted-foreground'">
                    {{ charCount }}/{{ maxLength }}
                </span>
            </div>
        </div>
    </div>
</template>
