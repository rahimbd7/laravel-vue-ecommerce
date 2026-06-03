<?php
namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder {
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void {
        if (ProductImage::count() > 0) {
            return;
        }

        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                $images = ProductImage::factory()
                    ->count(rand(1, 4))
                    ->state(['product_id' => $product->id, 'is_primary' => false])
                    ->create();

                $images->random()->update(['is_primary' => true]);
            }
        });
    }
}
