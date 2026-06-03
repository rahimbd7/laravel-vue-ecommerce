<?php
namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\ProductImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        $fileName  = $this->faker->lexify('product-??????') . '.jpg';
        $directory = 'products/' . $this->faker->word();

        return [
            'product_id'    => Product::query()->inRandomOrder()->value('id') ?? Product::factory(),
            'image_url'     => $directory . '/' . $fileName,
            'thumbnail_url' => $directory . '/thumb-' . $fileName,
            'medium_url'    => $directory . '/medium-' . $fileName,
            'large_url'     => $directory . '/large-' . $fileName,
            'is_primary'    => false,
            'alt_text'      => $this->faker->sentence(6),
            'title'         => $this->faker->words(3, true),
            'caption'       => $this->faker->sentence(10),
            'order'         => $this->faker->numberBetween(0, 9),
            'mime_type'     => 'image/jpeg',
            'file_size'     => $this->faker->numberBetween(18000, 480000),
        ];
    }
}
