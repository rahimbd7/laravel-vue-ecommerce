<?php

namespace App\Http\Controllers\API\Transaction;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
        $this->middleware('auth:sanctum');
    }

    public function myTransactions(Request $request)
    {
        $transactions = $this->transactionService->getUserTransactions(
            Auth::id(),
            $request->get('per_page', 15)
        );

        return $this->paginationResponse($transactions, 'Transactions retrieved successfully');
    }

    public function transactionSummary()
    {
        $summary = $this->transactionService->getTransactionSummary(Auth::id());
        return $this->successResponse($summary, 'Transaction summary retrieved successfully');
    }

    public function adminTransactions(Request $request)
    {
        // $this->authorize('viewAny', \App\Models\TransactionLog::class);

        $query = \App\Models\TransactionLog::with(['user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_role')) {
            $query->where('user_role', $request->user_role);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return $this->paginationResponse($transactions, 'Transactions retrieved successfully');
    }
}