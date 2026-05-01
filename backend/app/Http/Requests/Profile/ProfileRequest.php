<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
    public function messages(): array
    {
        return [
            'avatar.required' => 'Please select an image file.',
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'Allowed image formats: jpeg, png, jpg, gif, webp.',
            'avatar.max' => 'Maximum allowed file size is 2MB.',
        ];
    }
}
