<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image_url,
            'icon' => $this->icon,
            'parent_id' => $this->parent ? $this->parent->uuid : null,
            'position' => $this->position,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured ? true : false ,
            'product_count' => $this->product_count,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            //meta key words are array in the model, but we want to return them as a comma separated string in the API response
            'meta_keywords' => !empty($this->meta_keywords)
                ? implode(',', $this->meta_keywords)
                : '',
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
