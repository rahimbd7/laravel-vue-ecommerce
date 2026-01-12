<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class VendorService
{
    public function apply(User $user, array $data): Vendor
    {
        return DB::transaction(function () use ($user, $data) {
            // Check if already a vendor
            if ($user->vendor) {
                throw new \Exception(
                    $user->vendor->is_verified
                        ? 'You are already a verified vendor'
                        : 'Your vendor application is pending approval'
                );
            }

            $user->save();

            // Create vendor (unverified)
            $vendor = $user->vendor()->create([
                'business_name' => $data['business_name'],
                'business_email' => $data['business_email'],
                'business_phone' => $data['business_phone'],
                'tax_number' => $data['tax_number'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'commission_rate' => $data['commission_rate'] ?? 10.00,
                'is_verified' => false,
            ]);

            // TODO: Send notification to admin

            return $vendor->load('user');
        });
    }

    public function approve(Vendor $vendor): Vendor
    {
        return DB::transaction(function () use ($vendor) {
           //update vendor status
           $vendor->update(['is_verified' => true, 'verified_at' => now(),'status' => 'approved', ]);


           //updateuser role to vendor if not already
           if ($vendor->user && $vendor->user->role !== 'vendor') {
               $vendor->user->role = 'vendor';
               $vendor->user->save();
           }
              return $vendor;
        });

        // TODO: Send notification to vendor

        return $vendor->fresh();
    }

    public function reject(Vendor $vendor,String $rejectedReason): void
    {
       //is_varified false and optional rejection message and update user role to customer again

       $vendor->update(['is_verified' => false,'status' => 'rejected',
       'verified_at' => null,
       'rejection_reason' => $rejectedReason, 'rejected_at' => now()]);

       //revert user role to customer
       if($vendor->user && $vendor->user->role === 'vendor') {
           $vendor->user->role = 'customer';
           $vendor->user->save();

        // TODO: Send notification to vendor

       }
    }

    public function updateVendor(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);
        return $vendor->fresh();
    }

    public function getVendorStats(Vendor $vendor): array
    {
        return [
            'products_count' => $vendor->products()->count(),
            'pending_orders' => 0, // TODO: Add order relationship
            'total_sales' => 0, // TODO: Add sales calculation
            'commission_earned' => 0, // TODO: Add commission calculation
        ];
    }
}


//TODO: Refactor User apply method to use this service :Role unchanged
//TODO: Refactor Vendor update method to use this service: Role changed if approved
//TODO: Refactor Vendor reject method to use this service: Rejected with Message
