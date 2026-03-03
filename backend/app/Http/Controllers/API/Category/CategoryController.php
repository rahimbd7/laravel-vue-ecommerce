<?php

namespace App\Http\Controllers\API\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Trait\ApiResponseTrait;
use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Resources\PublicCategoryResource;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     use ApiResponseTrait;

    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
        $this->middleware(['auth:sanctum', 'role:admin'])->except(['index', 'show']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = $this->categoryService->getCategories($request);
        return $this->successResponse(
            PublicCategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    /**
     * Display the specified resource.
     */
     public function show(Category $category)
    {
        $category->load('parent', 'children');
        return $this->successResponse(
            new PublicCategoryResource($category),
            'Category retrieved successfully'
        );
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
