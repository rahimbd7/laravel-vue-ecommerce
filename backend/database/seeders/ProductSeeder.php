<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder {
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void {
        $this->seedVendors();
        $this->seedCategories();

        Product::factory()->count(40)->create();
    }

    protected function seedVendors(): void {
        if (Vendor::count() > 0) {
            return;
        }

        $vendors = [
            [
                'user_uuid'       => Str::uuid()->toString(),
                'business_name'   => 'Blue Orchard Co.',
                'business_email'  => 'vendor+orchard@example.com',
                'business_phone'  => '+1-555-0123',
                'description'     => 'Premium lifestyle and home goods retailer.',
                'is_verified'     => true,
                'status'          => 'approved',
                'commission_rate' => 12.50,
            ],
            [
                'user_uuid'       => Str::uuid()->toString(),
                'business_name'   => 'Echo Apparel',
                'business_email'  => 'vendor+echo@example.com',
                'business_phone'  => '+1-555-0456',
                'description'     => 'Modern clothing and accessories for everyday wear.',
                'is_verified'     => true,
                'status'          => 'approved',
                'commission_rate' => 10.00,
            ],
            [
                'user_uuid'       => Str::uuid()->toString(),
                'business_name'   => 'Tech Harbor',
                'business_email'  => 'vendor+harbor@example.com',
                'business_phone'  => '+1-555-0789',
                'description'     => 'Electronics, gadgets, and smart home essentials.',
                'is_verified'     => true,
                'status'          => 'approved',
                'commission_rate' => 8.75,
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }
    }

    protected function seedCategories(): void {
        if (Category::count() > 0) {
            return;
        }

        $categories = [
            ['name' => 'Home & Living', 'slug' => 'home-living', 'description' => 'Decor, furniture and lifestyle products.'],
            ['name' => 'Apparel', 'slug' => 'apparel', 'description' => 'Clothing, shoes and accessories.'],
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets, devices and accessories.'],
            ['name' => 'Beauty', 'slug' => 'beauty', 'description' => 'Cosmetics, skincare and wellness items.'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Fitness, outdoors and active gear.'],
            ['name' => 'Toys & Games', 'slug' => 'toys-games', 'description' => 'Fun products for kids and families.'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'uuid'        => Str::uuid()->toString(),
                'name'        => $category['name'],
                'slug'        => $category['slug'],
                'description' => $category['description'],
                'is_active'   => true,
            ]);
        }
    }
}
