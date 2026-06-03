<?php
namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariation>
 */
class ProductVariationFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        $product = Product::query()->inRandomOrder()->first() ?? Product::factory()->create();

        $variations = [
            ['name' => 'Small', 'attributes' => ['size' => 'S']],
            ['name' => 'Medium', 'attributes' => ['size' => 'M']],
            ['name' => 'Large', 'attributes' => ['size' => 'L']],
            ['name' => 'Extra Large', 'attributes' => ['size' => 'XL']],
            ['name' => 'Red', 'attributes' => ['color' => 'Red']],
            ['name' => 'Blue', 'attributes' => ['color' => 'Blue']],
            ['name' => 'Black', 'attributes' => ['color' => 'Black']],
            ['name' => 'White', 'attributes' => ['color' => 'White']],
            ['name' => 'Green', 'attributes' => ['color' => 'Green']],
            ['name' => 'Silver', 'attributes' => ['color' => 'Silver']],
        ];

        $variation     = $this->faker->randomElement($variations);
        $basePrice     = $this->faker->randomFloat(2, 10, 500);
        $comparePrice  = $this->faker->boolean(40) ? $basePrice + $this->faker->randomFloat(2, 5, 100) : null;
        $stockQuantity = $this->faker->numberBetween(0, 500);

        // Determine stock status
        if ($stockQuantity == 0) {
            $stockStatus = 'out_of_stock';
        } elseif ($stockQuantity < 10) {
            $stockStatus = 'low_stock';
        } else {
            $stockStatus = 'in_stock';
        }

        return [
            'uuid'                => Str::uuid(),
            'product_id'          => $product->id,
            'name'                => $variation['name'],
            'sku'                 => 'VAR-' . Str::upper(Str::random(3)) . '-' . $this->faker->numberBetween(1000, 9999),
            'barcode'             => $this->faker->ean13(),
            'attributes'          => $variation['attributes'],
            'price'               => $basePrice,
            'compare_price'       => $comparePrice,
            'cost_per_item'       => $this->faker->randomFloat(2, $basePrice * 0.2, $basePrice * 0.5),
            'stock_quantity'      => $stockQuantity,
            'low_stock_threshold' => 5,
            'stock_status'        => $stockStatus,
            'weight'              => $this->faker->randomFloat(2, 0.1, 5.0),
            'dimensions'          => json_encode([
                'length' => $this->faker->randomFloat(1, 5, 50),
                'width'  => $this->faker->randomFloat(1, 5, 50),
                'height' => $this->faker->randomFloat(1, 5, 50),
            ]),
            'is_visible'          => $this->faker->boolean(90),
            'is_default'          => false,
            'position'            => 0,
            'image_id'            => ProductImage::query()
                ->where('product_id', $product->id)
                ->inRandomOrder()
                ->value('id'),
        ];
    }

    /**
     * Indicate that the variation is default.
     */
    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the variation is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock_quantity' => 0,
            'stock_status'   => 'out_of_stock',
        ]);
    }

    /**
     * Indicate that the variation is low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(1, 9),
            'stock_status'   => 'low_stock',
        ]);
    }

    /**
     * Indicate that the variation is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(10, 500),
            'stock_status'   => 'in_stock',
        ]);
    }

    /**
     * Set specific product for the variation.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn(array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
