<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
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
           'user' => new UserResource($this->whenLoaded('user')),
           'business_name' => $this->business_name,
           'business_email' => $this->business_email,
           'business_phone' => $this->business_phone,
           'tax_number' => $this->tax_number,
           'website' => $this->website,
           'description' => $this->description,
           'commission_rate' => $this->commission_rate,
           'is_verified' => $this->is_verified,
           'user' => new UserResource($this->whenLoaded('user'), function ($user) {
               return $user->only(['uuid', 'name', 'email']);
           }),
           'created_at' => $this->created_at,
           'updated_at' => $this->updated_at,
       ];
    }
}
