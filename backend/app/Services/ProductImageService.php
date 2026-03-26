<?php
// app/Services/ProductImageService.php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ProductImageService
{
    protected $disk = 'public';
    protected $paths = [
        'original' => 'products/images/original',
        'thumbnail' => 'products/images/thumbnail',
        'medium' => 'products/images/medium',
        'large' => 'products/images/large',
    ];

    /**
     * Intervention Image v3 manager (we use the GD driver by default).
     */
    protected ImageManager $imageManager;

    public function __construct()
    {
        // NOTE: This project requires `intervention/image` v3 (no Laravel Facade).
        // Using GD is the most common option and is available on most PHP installs.
        $this->imageManager = ImageManager::gd();
    }

    /**
     * Upload and attach images to product
     */
    public function attachToProduct(Product $product, array $images, ?int $primaryIndex = null)
    {
        return DB::transaction(function () use ($product, $images, $primaryIndex) {
            $uploadedImages = [];
            $startOrder = (int) ($product->images()->max('order') ?? -1);
            $hasExistingImages = $product->images()->exists();

            foreach ($images as $index => $image) {
                if ($image instanceof UploadedFile) {
                    $imageData = $this->upload($image);
                    $imageData['product_id'] = $product->id;
                    $imageData['order'] = $startOrder + $index + 1;

                    // Primary selection strategy:
                    // 1) If primary_index is provided, respect it.
                    // 2) If product has no images yet, first uploaded image becomes primary.
                    // 3) Otherwise, keep existing primary untouched.
                    if ($primaryIndex !== null) {
                        $imageData['is_primary'] = ((int) $index === (int) $primaryIndex);
                    } else {
                        $imageData['is_primary'] = ! $hasExistingImages && $index === 0;
                    }

                    $uploadedImages[] = ProductImage::create($imageData);
                }
            }

            return $uploadedImages;
        });
    }

    /**
     * Sync images for product (replace existing)
     */
    public function syncForProduct(Product $product, array $images, ?int $primaryIndex = null)
    {
        return DB::transaction(function () use ($product, $images, $primaryIndex) {
            // Delete existing images
            foreach ($product->images as $image) {
                $this->delete($image);
            }

            // Upload new images
            return $this->attachToProduct($product, $images, $primaryIndex);
        });
    }

    /**
     * Upload single image and create variants
     */
    public function upload(UploadedFile $file)
    {
        try {
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $extension = strtolower($file->getClientOriginalExtension());

            // Upload original
            $originalPath = $file->storeAs($this->paths['original'], $filename, $this->disk);

            // Create and upload thumbnail (150x150)
            $thumbnailPath = $this->paths['thumbnail'] . '/' . $filename;
            $thumbnail = $this->imageManager
                ->read($file->getPathname())
                ->cover(150, 150)
                ->encodeByExtension($extension);
            Storage::disk($this->disk)->put($thumbnailPath, (string) $thumbnail);

            // Create and upload medium (400x400)
            $mediumPath = $this->paths['medium'] . '/' . $filename;
            $medium = $this->imageManager
                ->read($file->getPathname())
                ->cover(400, 400)
                ->encodeByExtension($extension);
            Storage::disk($this->disk)->put($mediumPath, (string) $medium);

            // Create and upload large (800x800)
            $largePath = $this->paths['large'] . '/' . $filename;
            $large = $this->imageManager
                ->read($file->getPathname())
                ->cover(800, 800)
                ->encodeByExtension($extension);
            Storage::disk($this->disk)->put($largePath, (string) $large);

            return [
                'image_url' => $originalPath,
                'thumbnail_url' => $thumbnailPath,
                'medium_url' => $mediumPath,
                'large_url' => $largePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Replace single image file while keeping same DB row (id/order/primary).
     */
    public function replaceFile(Product $product, ProductImage $image, UploadedFile $file, array $meta = [])
    {
        if ($image->product_id !== $product->id) {
            throw new \Exception('Image does not belong to this product');
        }

        return DB::transaction(function () use ($image, $file, $meta) {
            $newData = $this->upload($file);

            // Remove old files from storage first.
            $this->deleteFiles($image);

            $updateData = array_merge($newData, [
                'alt_text' => $meta['alt_text'] ?? $image->alt_text,
                'title' => $meta['title'] ?? $image->title,
                'caption' => $meta['caption'] ?? $image->caption,
                'order' => $meta['order'] ?? $image->order,
            ]);

            $image->update($updateData);

            return $image->fresh();
        });
    }

    /**
     * Delete image and its variants
     */
    public function delete(ProductImage $image)
    {
        try {
            // Delete files from storage
            $this->deleteFiles($image);

            // Delete record
            return $image->delete();

        } catch (\Exception $e) {
            throw new \Exception('Failed to delete image: ' . $e->getMessage());
        }
    }

    protected function deleteFiles(ProductImage $image): void
    {
        $files = [
            $image->image_url,
            $image->thumbnail_url,
            $image->medium_url,
            $image->large_url,
        ];

        foreach ($files as $file) {
            if ($file && Storage::disk($this->disk)->exists($file)) {
                Storage::disk($this->disk)->delete($file);
            }
        }
    }

    /**
     * Set primary image
     */
    public function setPrimary(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            throw new \Exception('Image does not belong to this product');
        }

        return DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);

            return $image;
        });
    }

    /**
     * Reorder images
     */
    public function reorder(Product $product, array $order)
    {
        return DB::transaction(function () use ($product, $order) {
            $imageIds = collect($order)
                ->map(fn($id) => (int) $id)
                ->filter(fn($id) => $id > 0)
                ->values();

            if ($imageIds->isEmpty()) {
                throw new \Exception('Order payload is empty or invalid.');
            }

            if ($imageIds->count() !== $imageIds->unique()->count()) {
                throw new \Exception('Order payload contains duplicate image IDs.');
            }

            $matchedCount = ProductImage::where('product_id', $product->id)
                ->whereIn('id', $imageIds->all())
                ->count();

            if ($matchedCount !== $imageIds->count()) {
                throw new \Exception('One or more image IDs do not belong to this product.');
            }

            foreach ($order as $position => $imageId) {
                ProductImage::where('product_id', $product->id)
                    ->where('id', $imageId)
                    ->update(['order' => $position]);
            }

            return true;
        });
    }
}
