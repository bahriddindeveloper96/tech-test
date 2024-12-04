<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class SellerProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all sellers
        $sellers = User::where('role', 'seller')->get();

        foreach ($sellers as $seller) {
            // Create 5 products for each seller
            for ($i = 1; $i <= 5; $i++) {
                $product = Product::create([
                    'seller_id' => $seller->id,
                    'category_id' => rand(1, 5), // Random category from 1-5
                    'price' => rand(100000, 1000000), // Random price between 100k and 1M
                    'old_price' => null,
                    'stock' => rand(10, 100), // Changed from quantity to stock
                    'status' => 'active',
                    'slug' => "product-{$i}-{$seller->id}-" . time(), // Added timestamp to make slug unique
                ]);

                // Add translations for the product
                $product->translations()->create([
                    'locale' => 'uz',
                    'name' => "Mahsulot {$i} - {$seller->company_name}",
                    'description' => "Bu {$seller->company_name} kompaniyasining {$i}-mahsuloti",
                ]);

                $product->translations()->create([
                    'locale' => 'ru',
                    'name' => "Продукт {$i} - {$seller->company_name}",
                    'description' => "Это {$i}-й продукт компании {$seller->company_name}",
                ]);

                $product->translations()->create([
                    'locale' => 'en',
                    'name' => "Product {$i} - {$seller->company_name}",
                    'description' => "This is product {$i} from {$seller->company_name}",
                ]);
            }
        }
    }
}
