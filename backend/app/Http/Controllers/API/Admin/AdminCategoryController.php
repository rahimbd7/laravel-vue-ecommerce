<?php
namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryApplicationRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminCategoryController extends Controller {
    use ApiResponseTrait;

    protected $categoryService;

    public function __construct(CategoryService $categoryService) {
        $this->categoryService = $categoryService;
        $this->middleware(['auth:sanctum', 'role:admin'])->except(['index', 'show']);
    }

    public function index(Request $request) {
        try {
            $categories = $this->categoryService->getCategoryListByAdmin($request);
            return $this->successResponse(
                CategoryResource::collection($categories), 
                'Categories retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(CategoryApplicationRequest $request) {

        try {
            $category = $this->categoryService->create($request->validated());
            return $this->successResponse(
                new CategoryResource($category),
                'Category created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show($id) {
        try {
            // Check if ID is numeric or UUID
            if (is_numeric($id)) {
                $category = $this->categoryService->getCategory($id);
            } else {
                $category = $this->categoryService->getCategory($id);
            }

            return $this->successResponse(
                new CategoryResource($category),
                'Category retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Category show error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function update(CategoryApplicationRequest $request, Category $category) {
        try {
            $category = $this->categoryService->update($category, $request->validated());
            return $this->successResponse(
                new CategoryResource($category),
                'Category updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy(Category $category) {
        try {
            $this->categoryService->delete($category);
            return $this->successResponse(
                null,
                'Category deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
