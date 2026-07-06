<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\VendorPayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalculateVendorEarnings implements ShouldQueue
{
    protected $vendorPayoutService;

    public function __construct(VendorPayoutService $vendorPayoutService)
    {
        $this->vendorPayoutService = $vendorPayoutService;
    }

    public function handle(OrderStatusChanged $event): void
    {
        // Only calculate when order becomes delivered or completed
        if (in_array($event->newStatus, ['delivered', 'completed'])) {
            $this->vendorPayoutService->calculateAndCreatePayouts($event->order);
        }
    }
}