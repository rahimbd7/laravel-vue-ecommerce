<?php
namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductVariationSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Skip if variations already exist
        if (ProductVariation::count() > 0) {
            return;
        }

        // Create variations for each product
        Product::chunk(100, function ($products) {
            foreach ($products as $product) {
                // Create 2-4 variations per product
                $variationCount = rand(2, 4);

                // Predefined variation options
                $variationOptions = [
                    [
                        'names'      => ['Small', 'Medium', 'Large', 'Extra Large'],
                        'attributes' => [
                            ['size' => 'S'],
                            ['size' => 'M'],
                            ['size' => 'L'],
                            ['size' => 'XL'],
                        ],
                    ],
                    [
                        'names'      => ['Red', 'Blue', 'Black', 'White'],
                        'attributes' => [
                            ['color' => 'Red'],
                            ['color' => 'Blue'],
                            ['color' => 'Black'],
                            ['color' => 'White'],
                        ],
                    ],
                ];

                $selectedOptions    = $variationOptions[array_rand($variationOptions)];
                $selectedVariations = array_slice($selectedOptions['names'], 0, $variationCount);
                $selectedAttributes = array_slice($selectedOptions['attributes'], 0, $variationCount);

                foreach ($selectedVariations as $index => $variationName) {
                    $basePrice     = $product->price ?? 50;
                    $stockQuantity = rand(0, 500);

                    // Determine stock status
                    if ($stockQuantity == 0) {
                        $stockStatus = 'out_of_stock';
                    } elseif ($stockQuantity < 10) {
                        $stockStatus = 'low_stock';
                    } else {
                        $stockStatus = 'in_stock';
                    }

                    ProductVariation::create([
                        'uuid'                => Str::uuid(),
                        'product_id'          => $product->id,
                        'name'                => $variationName,
                        'sku'                 => 'VAR-' . Str::upper(Str::random(3)) . '-' . rand(1000, 9999),
                        'barcode'             => rand(1000000000000, 9999999999999),
                        'attributes'          => $selectedAttributes[$index],
                        'price'               => $basePrice + rand(-5, 20),
                        'compare_price'       => rand(0, 1) ? $basePrice + rand(15, 50) : null,
                        'cost_per_item'       => $basePrice * 0.3,
                        'stock_quantity'      => $stockQuantity,
                        'low_stock_threshold' => 5,
                        'stock_status'        => $stockStatus,
                        'weight'              => round(rand(10, 500) / 100, 2),
                        'dimensions'          => json_encode([
                            'length' => rand(5, 50),
                            'width'  => rand(5, 50),
                            'height' => rand(5, 50),
                        ]),
                        'is_visible'          => rand(0, 1) ? true : (rand(0, 1) ? true : false),
                        'is_default'          => $index === 0 ? true : false,
                        'position'            => $index,
                    ]);
                }
            }
        });
    }
}
