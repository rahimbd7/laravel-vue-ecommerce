<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VendorApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'business_name' => 'required|string|max:255',
            'business_email' => 'required|email|max:255',
            'business_phone' => 'required|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
    public function messages()
    {
        return [
            'business_name.required' => 'Business name is required.',
            'business_email.required' => 'Business email is required.',
            'business_email.email' => 'Business email must be a valid email address.',
            'business_phone.required' => 'Business phone is required.',
            'commission_rate.numeric' => 'Commission rate must be a number.',
            'commission_rate.min' => 'Commission rate must be at least 0%.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',
        ];
    }
}
