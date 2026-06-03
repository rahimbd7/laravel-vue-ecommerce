<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'business_name'   => fake()->company(),
            'business_email'  => fake()->unique()->companyEmail(),
            'business_phone'  => fake()->phoneNumber(),
            'tax_number'      => fake()->numerify('TAX-###-###-###'),
            'website'         => fake()->url(),
            'description'     => fake()->paragraph(),
            'is_verified'     => true,
            'status'          => 'approved',
            'verified_at'     => now(),
            'commission_rate' => fake()->randomElement([5.00, 7.50, 10.00, 12.50, 15.00]),
        ];
    }

    /**
     * Set vendor with sequence (vendor1, vendor2, etc.)
     */
    public function withSequence(int $sequence): static
    {
        return $this->state(fn(array $attributes) => [
            'business_name' => "Vendor {$sequence}",
            'business_email'                          => "vendor{$sequence}@net",
            'business_phone' => fake()->phoneNumber(),
        ]);
    }

    /**
     * Make vendor unverified (pending status).
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => false,
            'status'      => 'pending',
            'verified_at' => null,
        ]);
    }

    /**
     * Make vendor rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified'      => false,
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
