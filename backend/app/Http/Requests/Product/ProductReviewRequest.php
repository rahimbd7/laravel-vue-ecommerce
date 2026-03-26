<?php
// app/Http/Requests/Product/ProductReviewRequest.php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductReviewRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'rating' => $isUpdate ? 'sometimes|integer|min:1|max:5' : 'required|integer|min:1|max:5',
            'comment' => $isUpdate ? 'sometimes|string|min:10' : 'required|string|min:10',
        ];
    }

    public function messages()
    {
        return [
            'rating.required' => 'Please select a rating.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'comment.required' => 'Please write a review comment.',
            'comment.min' => 'Review comment must be at least 10 characters.',
        ];
    }
}
