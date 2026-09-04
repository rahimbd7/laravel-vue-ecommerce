<?php

namespace Database\Seeders;

use App\Models\CommissionSetting;
use Illuminate\Database\Seeder;

class CommissionSettingsSeeder extends Seeder
{
    public function run()
    {
        CommissionSetting::create([
            'vendor_id' => null,
            'rate' => 8.00, // 8% commission
            'type' => 'percentage',
            'is_default' => true,
            'effective_from' => now(),
            'effective_to' => null,
        ]);

        $this->command->info('Default commission rate (8%) created successfully!');
    }
}