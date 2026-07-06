<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSetting extends Model
{
    protected $fillable = [
        'vendor_id',
        'rate',
        'type',
        'is_default',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_default' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public static function getRateForVendor(?int $vendorId = null): float
    {
        if ($vendorId) {
            $custom = self::where('vendor_id', $vendorId)
                ->where('effective_from', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', now());
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($custom) {
                return $custom->rate;
            }
        }

        $default = self::where('is_default', true)->first();
        return $default ? $default->rate : 10.00;
    }
}