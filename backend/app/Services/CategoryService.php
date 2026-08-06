<?php
namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryService {
    public function create(array $data): Category {
        return DB::transaction(function () use ($data) {
            // ✅ Store Cloudinary URL directly
            if (isset($data['image']) && is_string($data['image']) && filter_var($data['image'], FILTER_VALIDATE_URL)) {
                // It's a Cloudinary URL, store it directly
                $data['image'] = $data['image'];
            } elseif (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // Fallback: if it's an uploaded file (for backward compatibility)
                $data['image'] = $data['image']->store('categories', 'public');
            }

            // UUID generation
            if (! isset($data['uuid']) || empty($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }

            // Slug generation
            if (! isset($data['slug']) || empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }

            // Set defaults
            $data['is_active']     = $data['is_active'] ?? true;
            $data['is_featured']   = $data['is_featured'] ?? false;
            $data['position']      = $data['position'] ?? 0;
            $data['product_count'] = 0;

            return Category::create($data);
        });
    }
    public function getCategories($request) {
        $query = Category::with(['children' => function ($q) {
            $q->active()->orderBy('position');
        }])
            ->root()
            ->active()
            ->orderBy('position');

        // Additional filtering can be added here based on $request parameters

        return $query->get();
    }
    //get category by admin
    public function getCategoryListByAdmin($request) {
        $query = Category::with(['children' => function ($q) {
            $q->active()->orderBy('position');
        }])->withCount('products')
            ->active()
            ->orderBy('position');

        return $query->get();
    }
    //get single category
    public function getCategory($id) {
        $category = Category::with(['parent', 'children' => function ($q) {
            $q->orderBy('position');
        }])->find($id);

        if (! $category) {
            throw new \Exception('Category not found');
        }

        return $category;
    }
    public function update(Category $category, array $data): Category {
        return DB::transaction(function () use ($category, $data) {
            // Handle parent relationship
            if (isset($data['parent_id'])) {
                if (empty($data['parent_id'])) {
                    $data['parent_id'] = null;
                } else {
                    $parent = Category::find($data['parent_id']);
                    if (! $parent) {
                        throw new \Exception('Parent category not found');
                    }
                    if ($parent->id === $category->id) {
                        throw new \Exception('Category cannot be its own parent');
                    }
                }
            }

            // ✅ Handle Cloudinary URL
            if (isset($data['image'])) {
                // If it's a Cloudinary URL (string starting with http)
                if (is_string($data['image']) && filter_var($data['image'], FILTER_VALIDATE_URL)) {
                    // Store the Cloudinary URL directly
                    $data['image'] = $data['image'];
                }
                // If image is null or empty string, remove the image
                elseif ($data['image'] === null || $data['image'] === '') {
                    $data['image'] = null;
                }
                // Fallback: if it's an uploaded file (for backward compatibility)
                elseif ($data['image'] instanceof UploadedFile) {
                    // Delete old image if it was stored locally (not Cloudinary)
                    if ($category->image && ! filter_var($category->image, FILTER_VALIDATE_URL)) {
                        Storage::disk('public')->delete($category->image);
                    }
                    $data['image'] = $data['image']->store('categories', 'public');
                }
            }

            // Handle slug
            if (isset($data['slug']) && ! empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['slug'], $category->id);
            } elseif (isset($data['name']) && ! isset($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name'], $category->id);
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
