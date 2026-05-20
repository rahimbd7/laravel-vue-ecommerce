<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image_url,
            'icon' => $this->icon,
            'parent_uuid' => $this->parent ? $this->parent->uuid : null,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'children' => PublicCategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
