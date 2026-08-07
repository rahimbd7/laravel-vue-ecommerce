<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'urls' => [
                'original' => $this->full_image_url,      // ✅ Handles both local and Cloudinary
                'thumbnail' => $this->full_thumbnail_url,  // ✅ Handles both local and Cloudinary
                'medium' => $this->full_medium_url,        // ✅ Handles both local and Cloudinary
                'large' => $this->full_large_url,          // ✅ Handles both local and Cloudinary
            ],
            'is_primary' => $this->is_primary,
            'alt_text' => $this->alt_text,
            'title' => $this->title,
            'caption' => $this->caption,
            'order' => $this->order,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'formatted_size' => $this->when($this->file_size, function() {
                return $this->formatBytes($this->file_size);
            }),
            'source' => $this->cloudinary_public_id ? 'cloudinary' : 'local', // ✅ Identify image source
            'cloudinary_public_id' => $this->cloudinary_public_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
