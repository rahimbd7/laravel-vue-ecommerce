<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Resources\VendorResource;
use PHPUnit\Event\TestSuite\Loaded;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $vendors = Vendor::verified()
            ->with('user')
            ->when($request->has('search'), function ($query) use ($request) {
                return $query->where('business_name', 'like', '%' . $request->search . '%');
            })
            ->paginate(10);

        return VendorResource::collection($vendors);
    }

    /**
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
        $vendor = Vendor::with('user')->findOrFail($id);
        return new VendorResource($vendor);
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
