<?php

namespace App\Services;

use App\Models\TransactionLog;
use App\Models\User;

class TransactionService
{
    public function log(
        User $user,
        string $action,
        string $referenceType,
        int $referenceId,
        float $amount,
        string $status,
        string $description = null,
        array $metadata = []
    ): TransactionLog {
        return TransactionLog::create([
            'user_id' => $user->id,
            'user_role' => $user->role ?? 'customer',
            'action' => $action,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $status,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getUserTransactions(int $userId, int $perPage = 15)
    {
        return TransactionLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getTransactionsByReference(string $referenceType, int $referenceId)
    {
        return TransactionLog::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTransactionSummary(int $userId): array
    {
        $logs = TransactionLog::where('user_id', $userId);

        return [
            'total_transactions' => $logs->count(),
            'total_amount' => $logs->sum('amount'),
            'successful' => $logs->where('status', 'success')->count(),
            'pending' => $logs->where('status', 'pending')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
        ];
    }
}