<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\Vendor;

class CommissionService
{
    public function getRateForVendor(?int $vendorId = null): float
    {
        return CommissionSetting::getRateForVendor($vendorId);
    }

    public function setGlobalRate(float $rate): CommissionSetting
    {
        // Set all existing global rates as not default
        CommissionSetting::where('is_default', true)
            ->whereNull('vendor_id')
            ->update(['is_default' => false]);

        return CommissionSetting::create([
            'vendor_id' => null,
            'rate' => $rate,
            'type' => 'percentage',
            'is_default' => true,
            'effective_from' => now(),
        ]);
    }

    public function setVendorRate(int $vendorId, float $rate): CommissionSetting
    {
        return CommissionSetting::create([
            'vendor_id' => $vendorId,
            'rate' => $rate,
            'type' => 'percentage',
            'is_default' => false,
            'effective_from' => now(),
        ]);
    }

    public function calculateCommission(float $amount, ?int $vendorId = null): array
    {
        $rate = $this->getRateForVendor($vendorId);
        $commission = ($amount * $rate) / 100;
        
        return [
            'rate' => $rate,
            'commission' => $commission,
            'net_amount' => $amount - $commission,
        ];
    }
}