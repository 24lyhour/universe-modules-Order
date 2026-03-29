<?php

namespace Modules\Order\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\CartStatusEnum;
use Modules\Order\Models\Cart;
use Modules\Order\Models\CartItem;
use Modules\Outlet\Models\Outlet;
use Modules\Product\Models\Product;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $outlets = Outlet::all();
        $products = Product::all();

        if ($customers->isEmpty() || $outlets->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Skipping CartSeeder: Missing customers, outlets, or products.');
            return;
        }

        $statuses = [
            CartStatusEnum::Active,
            CartStatusEnum::Active,
            CartStatusEnum::Active,
            CartStatusEnum::Abandoned,
            CartStatusEnum::Expired,
        ];

        foreach ($customers->take(20) as $customer) {
            $outlet = $outlets->random();
            $status = $statuses[array_rand($statuses)];

            $cart = Cart::create([
                'uuid' => Str::uuid(),
                'customer_id' => $customer->id,
                'outlet_id' => $outlet->id,
                'status' => $status,
                'notes' => fake()->optional(0.3)->sentence(),
                'expires_at' => $status === CartStatusEnum::Active
                    ? now()->addDays(rand(1, 7))
                    : ($status === CartStatusEnum::Expired ? now()->subDays(rand(1, 5)) : null),
                'is_active' => $status === CartStatusEnum::Active,
            ]);

            // Add 1-5 items to the cart
            $itemCount = rand(1, 5);
            $selectedProducts = $products->random(min($itemCount, $products->count()));

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 5);
                $unitPrice = $product->price ?? fake()->randomFloat(2, 1, 50);

                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $unitPrice * $quantity,
                    'notes' => fake()->optional(0.2)->sentence(),
                ]);
            }
        }

        $this->command->info('CartSeeder: Created ' . Cart::count() . ' carts with items.');
    }
}
