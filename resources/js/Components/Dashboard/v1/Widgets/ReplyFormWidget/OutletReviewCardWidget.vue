<script setup lang="ts">
import { computed } from 'vue';
import { Star, MessageSquare, Heart, Eye, Pencil, Utensils, Truck, Package, HeartHandshake } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/composables/useTranslation';

const { __ } = useTranslation();

interface OutletReviewData {
    id: number;
    uuid?: string;
    customer_id?: number;
    outlet_id?: number;
    overall_rating: number;
    food_rating: number | null;
    service_rating: number | null;
    delivery_rating: number | null;
    packaging_rating: number | null;
    comment: string | null;
    tags?: string[];
    tag_labels?: string[];
    reply: string | null;
    has_reply: boolean;
    is_active: boolean;
    is_verified?: boolean;
    helpful_count?: number;
    created_at: string;
    formatted_date: string;
    average_rating?: number;
    customer?: {
        id: number;
        name: string;
        avatar?: string;
        total_spend?: number;
        total_reviews?: number;
    };
    outlet?: { id: number; name: string };
    order?: { id: number; order_number: string };
}

const props = defineProps<{
    review: OutletReviewData;
}>();

const emit = defineEmits<{
    (e: 'view', review: OutletReviewData): void;
    (e: 'edit', review: OutletReviewData): void;
    (e: 'reply', review: OutletReviewData): void;
    (e: 'delete', review: OutletReviewData): void;
    (e: 'like', review: OutletReviewData): void;
}>();

const renderStars = (rating: number | null) => {
    if (!rating) return [];
    return Array.from({ length: 5 }, (_, i) => i < rating);
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const stars = computed(() => renderStars(props.review.overall_rating));

const ratingCategories = computed(() => [
    { key: 'food_rating', label: 'Food', icon: Utensils, color: 'text-orange-500', bgColor: 'bg-orange-100 dark:bg-orange-900', value: props.review.food_rating },
    { key: 'service_rating', label: 'Service', icon: HeartHandshake, color: 'text-pink-500', bgColor: 'bg-pink-100 dark:bg-pink-900', value: props.review.service_rating },
    { key: 'delivery_rating', label: 'Delivery', icon: Truck, color: 'text-green-500', bgColor: 'bg-green-100 dark:bg-green-900', value: props.review.delivery_rating },
    { key: 'packaging_rating', label: 'Packaging', icon: Package, color: 'text-blue-500', bgColor: 'bg-blue-100 dark:bg-blue-900', value: props.review.packaging_rating },
].filter(cat => cat.value !== null));
</script>

<template>
    <Card class="overflow-hidden hover:shadow-md transition-shadow">
        <CardContent class="p-6">
            <div class="flex gap-4">
                <!-- Customer Avatar -->
                <Avatar class="h-16 w-16 shrink-0">
                    <AvatarImage v-if="review.customer?.avatar" :src="review.customer.avatar" />
                    <AvatarFallback class="text-lg">
                        {{ review.customer?.name ? getInitials(review.customer.name) : '?' }}
                    </AvatarFallback>
                </Avatar>

                <!-- Content -->
                <div class="flex-1 min-w-0 space-y-3">
                    <!-- Header: Name, Stats, Rating, Date -->
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-lg">
                                {{ review.customer?.name || __('Anonymous') }}
                            </h3>
                            <div class="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                <span v-if="review.customer?.total_spend">
                                    {{ __('Total Spend:') }} <strong>{{ formatCurrency(review.customer.total_spend) }}</strong>
                                </span>
                                <span v-if="review.customer?.total_reviews">
                                    {{ __('Total Review:') }} <strong>{{ review.customer.total_reviews }}</strong>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1">
                                <Star
                                    v-for="(filled, index) in stars"
                                    :key="index"
                                    class="h-4 w-4"
                                    :class="filled ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                />
                            </div>
                            <span class="text-sm text-muted-foreground">{{ review.formatted_date }}</span>
                        </div>
                    </div>

                    <!-- Outlet info -->
                    <div v-if="review.outlet" class="flex items-center gap-2">
                        <Badge variant="outline">{{ review.outlet.name }}</Badge>
                    </div>

                    <!-- Category Ratings -->
                    <div v-if="ratingCategories.length > 0" class="flex flex-wrap gap-2">
                        <div
                            v-for="cat in ratingCategories"
                            :key="cat.key"
                            :class="['flex items-center gap-1 px-2 py-1 rounded text-xs', cat.bgColor]"
                        >
                            <component :is="cat.icon" :class="['h-3 w-3', cat.color]" />
                            <span>{{ __(cat.label) }}</span>
                            <span class="font-medium">{{ cat.value }}/5</span>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="review.tag_labels?.length" class="flex flex-wrap gap-1">
                        <Badge v-for="tag in review.tag_labels" :key="tag" variant="secondary" class="text-xs">
                            {{ tag }}
                        </Badge>
                    </div>

                    <!-- Comment -->
                    <p v-if="review.comment" class="text-sm leading-relaxed">
                        {{ review.comment }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground italic">
                        {{ __('No comment provided') }}
                    </p>

                    <!-- Reply Section -->
                    <div v-if="review.reply" class="mt-3 p-3 bg-muted/50 rounded-lg border-l-4 border-primary">
                        <p class="text-xs font-medium text-muted-foreground mb-1">{{ __('Your Reply:') }}</p>
                        <p class="text-sm">{{ review.reply }}</p>
                    </div>

                    <!-- Status badges -->
                    <div class="flex items-center gap-2">
                        <Badge v-if="!review.is_active" variant="destructive">{{ __('Inactive') }}</Badge>
                        <Badge v-if="review.is_verified" variant="secondary">{{ __('Verified') }}</Badge>
                        <Badge v-if="review.has_reply" variant="outline" class="text-green-600 border-green-600">
                            <MessageSquare class="h-3 w-3 mr-1" />
                            {{ __('Replied') }}
                        </Badge>
                    </div>

                    <Separator />

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" @click="emit('view', review)">
                            <Eye class="h-4 w-4 mr-1" />
                            {{ __('View Details') }}
                        </Button>
                        <Button
                            v-if="!review.has_reply"
                            variant="outline"
                            size="sm"
                            @click="emit('reply', review)"
                        >
                            <MessageSquare class="h-4 w-4 mr-1" />
                            {{ __('Public Reply') }}
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            @click="emit('reply', review)"
                        >
                            <Pencil class="h-4 w-4 mr-1" />
                            {{ __('Edit Reply') }}
                        </Button>
                        <Button variant="ghost" size="sm" @click="emit('edit', review)">
                            <Pencil class="h-4 w-4 mr-1" />
                            {{ __('Edit') }}
                        </Button>
                        <Button variant="ghost" size="icon" class="ml-auto" @click="emit('like', review)">
                            <Heart
                                class="h-4 w-4"
                                :class="review.helpful_count ? 'fill-pink-500 text-pink-500' : ''"
                            />
                        </Button>
                        <span v-if="review.helpful_count" class="text-sm text-muted-foreground">
                            {{ review.helpful_count }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
