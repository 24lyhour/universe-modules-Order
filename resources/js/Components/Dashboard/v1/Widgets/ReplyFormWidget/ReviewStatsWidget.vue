<script setup lang="ts">
import { computed } from 'vue';
import { Star, TrendingUp } from 'lucide-vue-next';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';

const { __ } = useTranslation();

interface RatingStats {
    total: number;
    average_rating: number;
    '5_star': number;
    '4_star': number;
    '3_star': number;
    '2_star': number;
    '1_star': number;
    growth_percentage?: number;
}

const props = defineProps<{
    stats: RatingStats;
    dateRange?: string;
}>();

const formatNumber = (num: number) => {
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k';
    }
    return num.toString();
};

const ratingDistribution = computed(() => {
    const total = props.stats.total || 1;
    return [
        { stars: 5, count: props.stats['5_star'], color: 'bg-emerald-500', percentage: (props.stats['5_star'] / total) * 100 },
        { stars: 4, count: props.stats['4_star'], color: 'bg-purple-400', percentage: (props.stats['4_star'] / total) * 100 },
        { stars: 3, count: props.stats['3_star'], color: 'bg-yellow-400', percentage: (props.stats['3_star'] / total) * 100 },
        { stars: 2, count: props.stats['2_star'], color: 'bg-sky-400', percentage: (props.stats['2_star'] / total) * 100 },
        { stars: 1, count: props.stats['1_star'], color: 'bg-orange-400', percentage: (props.stats['1_star'] / total) * 100 },
    ];
});

const renderStars = (rating: number) => {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    return { fullStars, hasHalfStar, emptyStars: 5 - fullStars - (hasHalfStar ? 1 : 0) };
};

const stars = computed(() => renderStars(props.stats.average_rating));
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">{{ __('Reviews') }}</h2>
                <div v-if="dateRange" class="px-4 py-2 border rounded-lg text-sm">
                    {{ dateRange }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Reviews -->
                <div class="space-y-2">
                    <p class="text-sm text-muted-foreground">{{ __('Total Reviews') }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-3xl font-bold">{{ formatNumber(stats.total) }}</span>
                        <span
                            v-if="stats.growth_percentage"
                            class="flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full"
                            :class="stats.growth_percentage >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'"
                        >
                            <TrendingUp v-if="stats.growth_percentage >= 0" class="h-3 w-3" />
                            {{ stats.growth_percentage }}%
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ __('Growth in reviews on this year') }}</p>
                </div>

                <!-- Average Rating -->
                <div class="space-y-2">
                    <p class="text-sm text-muted-foreground">{{ __('Average Rating') }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-3xl font-bold">{{ stats.average_rating.toFixed(1) }}</span>
                        <div class="flex text-yellow-400">
                            <Star v-for="i in stars.fullStars" :key="'full-' + i" class="h-5 w-5 fill-current" />
                            <Star v-if="stars.hasHalfStar" class="h-5 w-5 fill-current opacity-50" />
                            <Star v-for="i in stars.emptyStars" :key="'empty-' + i" class="h-5 w-5 text-gray-300 dark:text-gray-600" />
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ __('Average rating on this year') }}</p>
                </div>

                <!-- Rating Distribution -->
                <div class="space-y-2">
                    <div v-for="item in ratingDistribution" :key="item.stars" class="flex items-center gap-2 text-sm">
                        <div class="flex items-center gap-1 w-8">
                            <Star class="h-3 w-3 text-yellow-400 fill-yellow-400" />
                            <span>{{ item.stars }}</span>
                        </div>
                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div
                                :class="item.color"
                                class="h-full rounded-full transition-all duration-300"
                                :style="{ width: item.percentage + '%' }"
                            />
                        </div>
                        <span class="w-12 text-right text-muted-foreground">{{ formatNumber(item.count) }}</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
