<?php
namespace Database\Factories;

use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        $price        = $this->faker->randomFloat(2, 10, 450);
        $comparePrice = $this->faker->boolean(40)
            ? $price + $this->faker->randomFloat(2, 5, 100)
            : null;

        $stockQuantity = $this->faker->numberBetween(0, 200);
        $stockStatus   = $stockQuantity <= 0
            ? 'out_of_stock'
            : ($stockQuantity <= 10 ? 'low_stock' : 'in_stock');

        $shippingType = $this->faker->randomElement(['physical', 'digital', 'service']);
        $weight       = $shippingType === 'physical'
            ? $this->faker->randomFloat(2, 0.1, 20)
            : null;
        $dimensions = $shippingType === 'physical'
            ? sprintf('%s x %s x %s', $this->faker->numberBetween(5, 120), $this->faker->numberBetween(5, 80), $this->faker->numberBetween(1, 50))
            : null;

        return [
            'vendor_id'         => Vendor::query()->inRandomOrder()->value('id') ?? Vendor::create([
                'user_uuid'       => $this->faker->uuid(),
                'business_name'   => $this->faker->company(),
                'business_email'  => $this->faker->unique()->companyEmail(),
                'business_phone'  => $this->faker->phoneNumber(),
                'description'     => $this->faker->sentence(12),
                'is_verified'     => true,
                'status'          => 'approved',
                'commission_rate' => $this->faker->randomFloat(2, 5, 20),
            ])->id,
            'category_id'       => Category::query()->inRandomOrder()->value('id') ?? Category::create([
                'uuid'        => $this->faker->uuid(),
                'name'        => $this->faker->unique()->word() . ' ' . $this->faker->word(),
                'slug'        => $this->faker->unique()->slug(),
                'description' => $this->faker->sentence(16),
                'is_active'   => true,
            ])->id,
            'name'              => $productName = ucfirst($this->faker->words(3, true)),
            'slug'              => str()->slug($productName),
            'description'       => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->sentence(14),
            'sku'               => strtoupper($this->faker->bothify('SKU-???-###')),
            'stock_quantity'    => $stockQuantity,
            'stock_status'      => $stockStatus,
            'price'             => $price,
            'compare_price'     => $comparePrice,
            'cost_per_item'     => $this->faker->randomFloat(2, max(1, $price * 0.25), max(2, $price * 0.75)),
            'is_visible'        => true,
            'is_featured'       => $this->faker->boolean(20),
            'has_variations'    => false,
            'is_taxable'        => true,
            'tax_rate'          => $this->faker->randomFloat(2, 0, 20),
            'weight'            => $weight,
            'dimensions'        => $dimensions,
            'shipping_type'     => $shippingType,
            'free_shipping'     => $this->faker->boolean(15),
            'meta_title'        => $productName . ' | ' . $this->faker->companySuffix(),
            'meta_description'  => $this->faker->sentence(18),
            'meta_keywords'     => $this->faker->words(6),
            'attributes'        => [
                'color'    => $this->faker->safeColorName(),
                'material' => $this->faker->randomElement(['Cotton', 'Polyester', 'Leather', 'Wood', 'Steel']),
                'size'     => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
            ],
            'tags'              => $this->faker->words(4),
            'sold_count'        => $this->faker->numberBetween(0, 1200),
            'average_rating'    => $this->faker->randomFloat(2, 3, 5),
            'review_count'      => $this->faker->numberBetween(0, 180),
        ];
    }
}
