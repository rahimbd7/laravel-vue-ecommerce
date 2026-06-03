<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VendorSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Create 5 vendors with their associated users
        for ($i = 1; $i <= 5; $i++) {
            // Create vendor user
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => "User {$i}",
                'email' => "user{$i}@user.net",
                'email_verified_at' => now(),
                'password'          => Hash::make("user{$i}231"), // user1231, user2231, etc.
                'role' => 'vendor',
            ]);

            // Create vendor associated with user
            Vendor::create([
                'user_uuid'     => $user->uuid,
                'business_name' => "Vendor {$i}",
                'business_email' => "vendor{$i}@vendor.net",
                'business_phone' => fake()->phoneNumber(),
                'tax_number' => sprintf("TAX-%03d-%03d-%03d", $i, $i, $i),
                'website' => "https://vendor{$i}.net",
                'description' => "Demo vendor account for vendor {$i}",
                'is_verified' => true,
                'status' => 'approved',
                'verified_at' => now(),
                'commission_rate' => 10.00,
            ]);
        }

        $this->command->info('5 vendors with users created successfully!');
    }
}
