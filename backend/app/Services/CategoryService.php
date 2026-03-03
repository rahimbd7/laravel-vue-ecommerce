<?php
namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class CategoryService {
    public function create(array $data): Category
    {
    return DB::transaction(function () use ($data) {
        // Image upload
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $data['image']->store('categories', 'public');
        }

        // UUID generation
        if (!isset($data['uuid']) || empty($data['uuid'])) {
            $data['uuid'] = Str::uuid();
        }

        // Slug generation
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Create and return
        return Category::create($data);
    });
    }
    public function getCategories($request)
    {
        $query = Category::with(['children' => function($q) {
            $q->active()->orderBy('position');
        }])
        ->root()
        ->active()
        ->orderBy('position');

        // Additional filtering can be added here based on $request parameters

        return $query->get();
    }

    //get single category
    public function getCategory($id)
    {
        $category = Category::with(['parent', 'children'])->find($id);
        if (!$category) {
            throw new \Exception('Category not found');
        }
        // if (!$category->active) {
        //     throw new \Exception('Category is inactive');
        // }
        return $category;
    }
    public function update(Category $category, array $data): Category {
        return DB::transaction(function () use ($category, $data) {
            if (isset($data['parent_id'])) {
                $parent = Category::where('id', $data['parent_id'])->firstOrFail();
                // Prevent circular reference
                if ($parent->id === $category->id) {
                    throw new \Exception('Category cannot be its own parent');
                }
                $data['parent_id'] = $parent->id;
            }

            if (isset($data['image'])) {
                // Delete old image
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $data['image'] = $data['image']->store('categories', 'public');
            }

            $category->update($data);

            return $category->fresh();
        });
    }

    public function delete(Category $category): void {
        DB::transaction(function () use ($category) {
            // Check if category has products

            // TODO:: uncomment this block after implementing product reassignment
            // if ($category->products()->count() > 0) {
            //     throw new \Exception('Cannot delete category with products. Please reassign products first.');
            // }

            // Delete image
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            // Move children to parent or make them root
            $children = $category->children;
            foreach ($children as $child) {
                $child->update(['parent_id' => $category->parent_id]);
            }
            $category->delete();
        });
    }

    private function generateSlug(string $name): string {
        $slug  = str()->slug($name);
        $count = Category::where('slug', 'like', $slug . '%')->count();

        return $count ? $slug . '-' . ($count + 1) : $slug;
    }
}
