<?php

namespace Modules\Order\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Models\Order;
use Modules\Order\Models\ProductReview;
use Modules\Product\Models\Product;

class ProductReviewSeeder extends Seeder
{
    /**
     * Sample review comments for realistic data.
     */
    private array $positiveComments = [
        'Excellent product! Exactly what I was looking for.',
        'Great quality and fast delivery. Highly recommend!',
        'Very satisfied with this purchase. Will buy again.',
        'Perfect! The product exceeded my expectations.',
        'Amazing taste and freshness. Love it!',
        'Best purchase I made this month. Thank you!',
        'Fantastic quality for the price. Very happy!',
        'Delicious! My family loved it.',
        'Fresh and well-packaged. Great service!',
        'Outstanding product. Five stars well deserved.',
    ];

    private array $neutralComments = [
        'Good product, nothing special but does the job.',
        'Average quality. Okay for the price.',
        'Decent product. Could be better.',
        'It was fine. Met my basic expectations.',
        'Not bad, but I expected a bit more.',
        'Satisfactory product. Would consider buying again.',
    ];

    private array $negativeComments = [
        'Not as expected. Quality could be improved.',
        'Product arrived late. A bit disappointed.',
        'Average at best. Won\'t be buying again.',
        'Expected better quality for this price.',
        'Packaging was damaged. Product was okay.',
    ];

    private array $merchantReplies = [
        'Thank you for your wonderful feedback! We\'re glad you enjoyed the product.',
        'We appreciate your kind words! Looking forward to serving you again.',
        'Thank you for choosing us! Your satisfaction is our priority.',
        'We\'re thrilled to hear you loved it! Thank you for the review.',
        'Thank you for your feedback. We\'ll work on improving.',
        'We apologize for any inconvenience. Please contact us for assistance.',
        'Thanks for your honest review. We value your feedback!',
        'We appreciate you taking the time to share your experience.',
    ];

    private array $reviewedItemIds = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $completedOrders = Order::whereIn('status', [
            OrderStatusEnum::Delivered->value,
            OrderStatusEnum::Completed->value,
        ])->with('items')->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Skipping ProductReviewSeeder: Missing customers or products.');
            return;
        }

        if ($completedOrders->isEmpty()) {
            $this->command->warn('Skipping ProductReviewSeeder: No completed orders found.');
            return;
        }

        // Get already reviewed item IDs to avoid duplicates
        $this->reviewedItemIds = ProductReview::pluck('order_item_id')->toArray();

        // Create reviews from completed orders
        foreach ($completedOrders as $order) {
            if ($order->items->isEmpty()) {
                continue;
            }

            // Filter out already reviewed items
            $availableItems = $order->items->filter(function ($item) {
                return !in_array($item->id, $this->reviewedItemIds);
            });

            if ($availableItems->isEmpty()) {
                continue;
            }

            // Review 1-2 items from each order
            $itemsToReview = $availableItems->random(min(rand(1, 2), $availableItems->count()));

            foreach ($itemsToReview as $item) {
                // Double-check to avoid race conditions
                if (in_array($item->id, $this->reviewedItemIds)) {
                    continue;
                }

                $rating = $this->weightedRating();
                $comment = $this->getCommentByRating($rating);
                $hasReply = fake()->boolean(40);

                ProductReview::create([
                    'uuid' => Str::uuid(),
                    'customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'images' => fake()->optional(0.2)->randomElements([
                        'reviews/img1.jpg',
                        'reviews/img2.jpg',
                        'reviews/img3.jpg',
                    ], rand(1, 2)),
                    'reply' => $hasReply ? $this->merchantReplies[array_rand($this->merchantReplies)] : null,
                    'replied_at' => $hasReply ? fake()->dateTimeBetween($order->created_at, 'now') : null,
                    'is_active' => true,
                    'is_verified' => true,
                    'helpful_count' => rand(0, 50),
                    'created_at' => fake()->dateTimeBetween($order->created_at, 'now'),
                ]);

                // Track this item as reviewed
                $this->reviewedItemIds[] = $item->id;
            }
        }

        $this->command->info('ProductReviewSeeder: Created ' . ProductReview::count() . ' product reviews.');
    }

    /**
     * Generate weighted rating (more positive reviews).
     */
    private function weightedRating(): int
    {
        $weights = [
            5 => 40,
            4 => 30,
            3 => 15,
            2 => 10,
            1 => 5,
        ];

        $total = array_sum($weights);
        $random = rand(1, $total);
        $current = 0;

        foreach ($weights as $rating => $weight) {
            $current += $weight;
            if ($random <= $current) {
                return $rating;
            }
        }

        return 5;
    }

    /**
     * Get comment based on rating.
     */
    private function getCommentByRating(int $rating): ?string
    {
        if (fake()->boolean(20)) {
            return null;
        }

        if ($rating >= 4) {
            return $this->positiveComments[array_rand($this->positiveComments)];
        } elseif ($rating === 3) {
            return $this->neutralComments[array_rand($this->neutralComments)];
        } else {
            return $this->negativeComments[array_rand($this->negativeComments)];
        }
    }
}
