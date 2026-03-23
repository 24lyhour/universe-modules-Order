<script setup lang="ts">
import { computed } from 'vue';
import { Star, MessageSquare, Heart, Eye, Pencil, Trash2 } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/composables/useTranslation';

const { __ } = useTranslation();

interface ReviewData {
    id: number;
    uuid?: string;
    rating: number;
    comment: string | null;
    reply: string | null;
    has_reply: boolean;
    is_active: boolean;
    is_verified?: boolean;
    helpful_count?: number;
    created_at: string;
    formatted_date: string;
    customer?: {
        id: number;
        name: string;
        avatar?: string;
        total_spend?: number;
        total_reviews?: number;
    };
    product?: { id: number; name: string; sku?: string };
    outlet?: { id: number; name: string };
    order?: { id: number; order_number: string };
}

const props = defineProps<{
    review: ReviewData;
    showProduct?: boolean;
    showOutlet?: boolean;
}>();

const emit = defineEmits<{
    view: [review: ReviewData];
    edit: [review: ReviewData];
    reply: [review: ReviewData];
    delete: [review: ReviewData];
    like: [review: ReviewData];
}>();

const renderStars = (rating: number) => {
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

const stars = computed(() => renderStars(props.review.rating));
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

                    <!-- Product/Outlet info -->
                    <div v-if="showProduct && review.product" class="flex items-center gap-2">
                        <Badge variant="outline">{{ review.product.name }}</Badge>
                        <Badge v-if="review.product.sku" variant="secondary" class="text-xs">{{ review.product.sku }}</Badge>
                    </div>
                    <div v-if="showOutlet && review.outlet" class="flex items-center gap-2">
                        <Badge variant="outline">{{ review.outlet.name }}</Badge>
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
