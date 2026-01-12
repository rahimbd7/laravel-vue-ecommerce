<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'vendor']);
    }
    public function index()
    {
        //
        $vendor = $this->user()->vendor;
        return $this->successResponse([
           'vendor' => $vendor,
           'stat'=>[
            'products_count' => $vendor->products()->count() ?? 0,
            'orders_count' => $vendor->orders()->count() ?? 0,
            'total_sales' => $vendor->orders()->sum('total_amount'),
            'commissions_earned' => $vendor->orders()->where('status', 'completed')->sum('commission_amount')?? 0,
           ],
           'recent_activities' =>[]
        ],'Vendor Dashboard Data Retrieved Successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
