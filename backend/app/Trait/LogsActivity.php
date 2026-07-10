<?php

namespace App\Trait;

use App\Models\TransactionLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Log an activity to the transaction logs
     */
    protected function logActivity(
        string $action,
        string $referenceType,
        int $referenceId,
        float $amount = 0,
        string $status = 'success',
        ?string $description = null,
        array $metadata = []
    ) {
        $user = Auth::user();

        return TransactionLog::create([
            'user_id' => $user?->id,
            'user_role' => $user?->role ?? 'system',
            'action' => $action,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'status' => $status,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
