<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\VendorBalance;
use App\Models\VendorPayout;
use App\Models\CommissionSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVendorPayouts extends Command
{
    protected $signature = 'vendor:backfill-payouts {--dry-run : Preview without making changes}';
    protected $description = 'Backfill vendor payouts for existing delivered orders';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->line('');
        }

        // Get orders that are delivered/completed and paid
        $orders = Order::whereIn('status', ['delivered', 'completed'])
            ->where('payment_status', 'paid')
            ->get();

        $this->info("📦 Found {$orders->count()} delivered orders to process");

        if ($orders->isEmpty()) {
            $this->warn('No delivered orders found to backfill.');
            return 0;
        }

        $processed = 0;
        $skipped = 0;
        $bar = $this->output->createProgressBar($orders->count());

        foreach ($orders as $order) {
            // Skip if already processed
            if (VendorPayout::where('order_id', $order->id)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Group items by vendor
            $itemsByVendor = $order->items->groupBy('product.vendor_id');

            foreach ($itemsByVendor as $vendorId => $items) {
                if (!$vendorId) continue;

                $totalAmount = $items->sum(function ($item) {
                    return $item->unit_price * $item->quantity;
                });

                $commissionRate = CommissionSetting::getRateForVendor($vendorId);
                $commission = ($totalAmount * $commissionRate) / 100;
                $netAmount = $totalAmount - $commission;

                if ($dryRun) {
                    $this->line("Would process: Order #{$order->order_number} | Vendor ID: {$vendorId} | Total: \${$totalAmount} | Net: \${$netAmount}");
                } else {
                    DB::transaction(function () use ($order, $vendorId, $totalAmount, $commission, $netAmount) {
                        // Create payout record
                        VendorPayout::create([
                            'order_id' => $order->id,
                            'vendor_id' => $vendorId,
                            'total_amount' => $totalAmount,
                            'commission' => $commission,
                            'net_amount' => $netAmount,
                            'platform_fee' => $commission,
                            'status' => 'paid', // Mark as paid for existing orders
                            'paid_at' => now(),
                            'notes' => "Backfilled for order #{$order->order_number}",
                        ]);

                        // Update vendor balance
                        $balance = VendorBalance::firstOrNew(['vendor_id' => $vendorId]);
                        $balance->total_earned += $netAmount;
                        $balance->available_balance += $netAmount;
                        $balance->total_commission += $commission;
                        $balance->save();
                    });

                    $processed++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        if ($dryRun) {
            $this->info("🔍 Dry run completed. Would process {$processed} vendor payouts.");
        } else {
            $this->info("✅ Processed {$processed} vendor payouts successfully!");
            $this->info("⏭️ Skipped {$skipped} orders (already processed)");
        }

        return 0;
    }
}