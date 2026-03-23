<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, type VNode } from 'vue';
import {
    ArrowLeft,
    Star,
    User,
    Package,
    Calendar,
    MessageSquare,
    CheckCircle,
    XCircle,
    Send,
    ToggleLeft,
    ToggleRight,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { toast } from '@/composables/useToast';
import TiptapEditor from '@/components/TiptapEditor.vue';

interface ProductReviewItem {
    id: number;
    uuid: string;
    rating: number;
    comment: string | null;
    images: string[];
    reply: string | null;
    replied_at: string | null;
    is_active: boolean;
    is_verified: boolean;
    helpful_count: number;
    created_at: string;
    customer?: { id: number; name: string; avatar?: string };
    product?: { id: number; name: string; sku?: string; image?: string };
    order?: { id: number; order_number: string };
    formatted_date: string;
    has_reply: boolean;
}

defineOptions({
    layout: (h: (type: unknown, props: unknown, children: unknown) => VNode, page: VNode) =>
        h(AppLayout, { breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Product Reviews', href: '/dashboard/product-reviews' },
            { title: 'Review Details', href: '#' },
        ]}, () => page),
});

const props = defineProps<{ review: ProductReviewItem }>();

const isSubmitting = ref(false);
const replyForm = useForm({ reply: '' });

const handleBack = () => router.visit('/dashboard/product-reviews');

const handleReply = () => {
    if (!replyForm.reply.trim()) {
        toast.error('Please enter a reply');
        return;
    }
    isSubmitting.value = true;
    router.post(`/dashboard/product-reviews/${props.review.id}/reply`, {
        reply: replyForm.reply,
    }, {
        onSuccess: () => {
            toast.success('Reply sent successfully');
            replyForm.reset();
        },
        onFinish: () => { isSubmitting.value = false; },
    });
};

const handleToggleActive = () => {
    router.put(`/dashboard/product-reviews/${props.review.id}/toggle-active`, {}, {
        onSuccess: () => {
            toast.success(`Review ${props.review.is_active ? 'deactivated' : 'activated'} successfully`);
        },
    });
};

const formatDate = (date: string | null) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'long', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const renderStars = (rating: number) => '★'.repeat(rating) + '☆'.repeat(5 - rating);
</script>

<template>
    <Head title="Product Review Details" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="handleBack">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Product Review</h1>
                    <p class="text-muted-foreground">Review details and management</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" @click="handleToggleActive">
                    <component :is="review.is_active ? ToggleRight : ToggleLeft" class="mr-2 h-4 w-4" />
                    {{ review.is_active ? 'Deactivate' : 'Activate' }}
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Review Content -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <Star class="h-5 w-5 text-yellow-500" />
                                Review
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <Badge :variant="review.is_active ? 'default' : 'destructive'">
                                    {{ review.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                                <Badge v-if="review.is_verified" variant="secondary">
                                    <CheckCircle class="mr-1 h-3 w-3" />
                                    Verified
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Rating -->
                        <div class="flex items-center gap-2">
                            <span class="text-2xl text-yellow-500">{{ renderStars(review.rating) }}</span>
                            <span class="text-lg font-semibold">{{ review.rating }}/5</span>
                        </div>

                        <!-- Comment -->
                        <div v-if="review.comment" class="p-4 bg-muted rounded-lg">
                            <p class="whitespace-pre-wrap">{{ review.comment }}</p>
                        </div>
                        <p v-else class="text-muted-foreground italic">No comment provided</p>

                        <!-- Images -->
                        <div v-if="review.images?.length" class="flex flex-wrap gap-2">
                            <img
                                v-for="(image, idx) in review.images"
                                :key="idx"
                                :src="image"
                                alt="Review image"
                                class="h-24 w-24 rounded-lg object-cover"
                            />
                        </div>

                        <!-- Helpful count -->
                        <div class="text-sm text-muted-foreground">
                            {{ review.helpful_count }} people found this helpful
                        </div>
                    </CardContent>
                </Card>

                <!-- Reply Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MessageSquare class="h-5 w-5" />
                            Merchant Reply
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Existing Reply -->
                        <div v-if="review.reply" class="p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border-l-4 border-blue-500">
                            <p class="whitespace-pre-wrap">{{ review.reply }}</p>
                            <p class="text-sm text-muted-foreground mt-2">
                                Replied on {{ formatDate(review.replied_at) }}
                            </p>
                        </div>

                        <!-- Reply Form -->
                        <div v-if="!review.reply" class="space-y-4">
                            <TiptapEditor
                                v-model="replyForm.reply"
                                placeholder="Write your reply to the customer..."
                                rows="4"
                            />
                            <Button :disabled="isSubmitting" @click="handleReply">
                                <Send class="mr-2 h-4 w-4" />
                                {{ isSubmitting ? 'Sending...' : 'Send Reply' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Customer Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Customer
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="review.customer">
                            <p class="font-medium">{{ review.customer.name }}</p>
                        </div>
                        <p v-else class="text-muted-foreground">Guest Customer</p>
                    </CardContent>
                </Card>

                <!-- Product Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Product
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="review.product">
                            <p class="font-medium">{{ review.product.name }}</p>
                            <p v-if="review.product.sku" class="text-sm text-muted-foreground">
                                SKU: {{ review.product.sku }}
                            </p>
                        </div>
                        <p v-else class="text-muted-foreground">Product not found</p>
                    </CardContent>
                </Card>

                <!-- Order Info -->
                <Card v-if="review.order">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5" />
                            Order
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="font-medium">{{ review.order.order_number }}</p>
                        <Button variant="link" class="p-0 h-auto" @click="router.visit(`/dashboard/orders/${review.order.id}`)">
                            View Order
                        </Button>
                    </CardContent>
                </Card>

                <!-- Timeline -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Timeline
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Created</p>
                            <p class="text-sm font-medium">{{ formatDate(review.created_at) }}</p>
                        </div>
                        <div v-if="review.replied_at">
                            <p class="text-sm text-muted-foreground">Replied</p>
                            <p class="text-sm font-medium">{{ formatDate(review.replied_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
