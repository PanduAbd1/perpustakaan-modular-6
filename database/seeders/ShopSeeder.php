<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::factory()->count(10)->create();

        $products = Product::factory()->count(50)->create();

        $products->each(function ($product) use ($categories) {
            $product->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray(),
                ['added_at' => now()]
            );
        });
    }
}
