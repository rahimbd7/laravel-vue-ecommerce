<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBalance extends Model
{
    protected $fillable = [
        'vendor_id',
        'pending_balance',
        'available_balance',
        'total_earned',
        'total_commission',
    ];

    protected $casts = [
        'pending_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_commission' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function addEarnings(float $amount, float $commission): void
    {
        $this->pending_balance += $amount;
        $this->total_earned += $amount;
        $this->total_commission += $commission;
        $this->save();
    }

    public function processPayout(float $amount): void
    {
        $this->pending_balance -= $amount;
        $this->available_balance += $amount;
        $this->save();
    }

    public function deductForRefund(float $amount): void
    {
        if ($this->pending_balance >= $amount) {
            $this->pending_balance -= $amount;
        } elseif ($this->available_balance >= $amount) {
            $this->available_balance -= $amount;
        } else {
            throw new \Exception('Insufficient balance for refund');
        }
        $this->total_earned -= $amount;
        $this->save();
    }
}