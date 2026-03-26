<?php


// app/Http/Requests/Product/ProductImageRequest.php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Authorization handled in controller
    }

    public function rules()
    {
        $rules = [];
        $actionMethod = $this->route()?->getActionMethod();

        if ($actionMethod === 'reorder') {
            $rules = [
                'order' => 'required|array|min:1',
                'order.*' => 'required|integer|exists:product_images,id',
            ];
        } elseif ($this->isMethod('POST')) {
            $imageCount = count($this->file('images', []));
            $maxPrimaryIndex = max(0, $imageCount - 1);

            $rules = [
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'primary_index' => 'nullable|integer|min:0|max:' . $maxPrimaryIndex,
            ];
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'image' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'images' => 'sometimes|array|size:1',
                'images.*' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'alt_text' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'caption' => 'nullable|string|max:500',
                'order' => 'sometimes|integer|min:0',
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'images.required' => 'Please upload at least one image.',
            'images.*.image' => 'File must be an image.',
            'images.*.mimes' => 'Image must be jpeg, png, jpg, gif, or webp format.',
            'images.*.max' => 'Image size cannot exceed 5MB.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or webp format.',
            'image.max' => 'Image size cannot exceed 5MB.',
            'images.size' => 'For update, send only one replacement file in images[].',
            'primary_index.max' => 'Primary index must match one of uploaded images.',
            'order.required' => 'Please provide image ids in desired order.',
        ];
    }
}
