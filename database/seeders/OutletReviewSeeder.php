<?php

namespace Modules\Order\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Models\Order;
use Modules\Order\Models\OutletReview;
use Modules\Outlet\Models\Outlet;

class OutletReviewSeeder extends Seeder
{
    /**
     * Sample review comments for outlets.
     */
    private array $positiveComments = [
        'Amazing experience! The food was delicious and delivery was super fast.',
        'Best restaurant in town! Always fresh and tasty.',
        'Love this place! Great food, friendly service, and quick delivery.',
        'Excellent service every time I order. Highly recommend!',
        'The food quality is outstanding. My go-to restaurant!',
        'Fast delivery and everything was still hot. Perfect!',
        'Great packaging, food arrived in perfect condition.',
        'Consistently good food and service. Five stars!',
        'Amazing taste! The staff is always friendly and helpful.',
        'Best food delivery experience I\'ve had. Will order again!',
    ];

    private array $neutralComments = [
        'Food was okay. Delivery took a bit longer than expected.',
        'Average experience. Nothing special but decent food.',
        'Good food but the packaging could be better.',
        'Satisfactory service. Would try again.',
        'Decent food quality. Expected slightly better.',
        'Okay experience overall. Room for improvement.',
    ];

    private array $negativeComments = [
        'Food was cold when it arrived. Disappointed.',
        'Delivery took way too long. Food quality suffered.',
        'Order was incorrect. Customer service needs improvement.',
        'Packaging was messy. Not happy with this order.',
        'Expected better based on the reviews. Not satisfied.',
    ];

    private array $merchantReplies = [
        'Thank you so much for your kind words! We\'re delighted you enjoyed the experience.',
        'We truly appreciate your feedback! Your satisfaction means everything to us.',
        'Thank you for choosing us! We hope to serve you again soon.',
        'We\'re thrilled to hear about your positive experience! Thank you!',
        'We sincerely apologize for the inconvenience. Please reach out to us directly.',
        'Thank you for your honest feedback. We\'re working on improving.',
        'We appreciate your patience and understanding. We\'ll do better!',
        'Thank you for taking the time to review us. Your feedback helps us grow.',
    ];

    private array $reviewedOrderIds = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $outlets = Outlet::all();
        $completedOrders = Order::whereIn('status', [
            OrderStatusEnum::Delivered->value,
            OrderStatusEnum::Completed->value,
        ])->get();

        if ($customers->isEmpty() || $outlets->isEmpty()) {
            $this->command->warn('Skipping OutletReviewSeeder: Missing customers or outlets.');
            return;
        }

        if ($completedOrders->isEmpty()) {
            $this->command->warn('Skipping OutletReviewSeeder: No completed orders found.');
            return;
        }

        // Get already reviewed order IDs to avoid duplicates
        $this->reviewedOrderIds = OutletReview::pluck('order_id')->toArray();

        $availableTags = array_keys(OutletReview::TAGS);

        // Create reviews from completed orders (one review per order)
        foreach ($completedOrders as $order) {
            // Skip if order already has a review
            if (in_array($order->id, $this->reviewedOrderIds)) {
                continue;
            }

            $overallRating = $this->weightedRating();
            $comment = $this->getCommentByRating($overallRating);
            $hasReply = fake()->boolean(45);

            // Generate category ratings (can be null)
            $foodRating = fake()->optional(0.8)->numberBetween(max(1, $overallRating - 1), min(5, $overallRating + 1));
            $serviceRating = fake()->optional(0.7)->numberBetween(max(1, $overallRating - 1), min(5, $overallRating + 1));
            $deliveryRating = fake()->optional(0.9)->numberBetween(max(1, $overallRating - 1), min(5, $overallRating + 1));
            $packagingRating = fake()->optional(0.6)->numberBetween(max(1, $overallRating - 1), min(5, $overallRating + 1));

            // Select random tags based on rating
            $selectedTags = $overallRating >= 4
                ? fake()->randomElements($availableTags, rand(2, 4))
                : ($overallRating >= 3 ? fake()->optional(0.5)->randomElements($availableTags, rand(1, 2)) : null);

            OutletReview::create([
                'uuid' => Str::uuid(),
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'outlet_id' => $order->outlet_id,
                'overall_rating' => $overallRating,
                'food_rating' => $foodRating,
                'service_rating' => $serviceRating,
                'delivery_rating' => $deliveryRating,
                'packaging_rating' => $packagingRating,
                'comment' => $comment,
                'images' => fake()->optional(0.15)->randomElements([
                    'reviews/outlet1.jpg',
                    'reviews/outlet2.jpg',
                    'reviews/outlet3.jpg',
                ], rand(1, 2)),
                'tags' => $selectedTags,
                'reply' => $hasReply ? $this->merchantReplies[array_rand($this->merchantReplies)] : null,
                'replied_at' => $hasReply ? fake()->dateTimeBetween($order->created_at, 'now') : null,
                'is_active' => true,
                'is_verified' => true,
                'helpful_count' => rand(0, 100),
                'created_at' => fake()->dateTimeBetween($order->created_at, 'now'),
            ]);

            // Track this order as reviewed
            $this->reviewedOrderIds[] = $order->id;
        }

        $this->command->info('OutletReviewSeeder: Created ' . OutletReview::count() . ' outlet reviews.');
    }

    /**
     * Generate weighted rating (more positive reviews).
     */
    private function weightedRating(): int
    {
        $weights = [
            5 => 35,
            4 => 35,
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
        if (fake()->boolean(15)) {
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
