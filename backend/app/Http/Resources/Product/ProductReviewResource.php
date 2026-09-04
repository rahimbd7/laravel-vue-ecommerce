<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'rating_stars' => $this->rating_stars,
            'title' => $this->title,
            'comment' => $this->comment,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url ?? null,
            ],
            'images' => $this->images_full_url,
            'votes' => [
                'helpful' => $this->helpful_votes,
                'unhelpful' => $this->unhelpful_votes,
                'helpful_percentage' => $this->helpful_percentage,
            ],
            'flags' => [
                'is_verified_purchase' => $this->is_verified_purchase,
                'is_approved' => $this->is_approved,
                'is_featured' => $this->is_featured,
            ],
            'created_at' => $this->created_at,
            'created_ago' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at,
        ];
    }
}
