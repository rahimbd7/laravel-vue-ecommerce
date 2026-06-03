<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void {
        // Create admin user
        User::factory()->create([
            'uuid'     => 'admin-uuid-0000-0000-000000000000',
            'name'     => 'Admin User',
            'email'    => 'admin@admin.com',
            'password' => Hash::make("admin1234"),
            'role'     => "admin",
        ]);

        // Call all seeders in logical order
        $this->call([
            VendorSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductVariationSeeder::class,
            ProductImageSeeder::class,
            ProductReviewSeeder::class,
            CartSeeder::class,
            CartItemSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
            ProfileSeeder::class,
        ]);
    }
}
