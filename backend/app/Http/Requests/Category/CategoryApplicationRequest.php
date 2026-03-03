<?php
namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class CategoryApplicationRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     *
     */
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'name'             => $this->isMethod('post') ? 'required|string|max:255' : 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'parent_id'        => 'nullable|exists:categories,id',
            'image'            => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'icon'             => 'nullable|string|max:50',
            'position'         => 'nullable|integer',
            'is_active'        => 'sometimes|boolean',
            'is_featured'      => 'sometimes|boolean',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords'    => 'nullable|array',
            'meta_keywords.*'  => 'nullable|max:100',
        ];
    }
    protected function prepareForValidation() {
        // Get all data first
        $data = $this->all();
        // 1. Handle JSON payload if present
        if (isset($data['payload']) && is_string($data['payload'])) {
            $jsonData = json_decode($data['payload'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                $data = array_merge($data, $jsonData);
                unset($data['payload']);
            }
        }
        //throw error if json decode failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException(Validator::make([], []), response()->json([
                'message' => 'Invalid JSON payload',
                'errors' => ['payload' => ['The payload field must be a valid JSON string.']]
            ], 422));
        }
        // 2. Handle meta_keywords if it comes as JSON string
        if (isset($data['meta_keywords']) && is_string($data['meta_keywords'])) {
            $decoded = json_decode($data['meta_keywords'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data['meta_keywords'] = $decoded;
            }
        }
        // 3. FIXED: Better boolean conversion
        foreach (['is_active', 'is_featured'] as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                if (is_string($value)) {
                    $lowerValue   = strtolower(trim($value));
                    $data[$field] = in_array($lowerValue, ['true', '1', 'yes', 'on'], true);
                } elseif (is_numeric($value)) {
                    $data[$field] = (bool) $value;
                }
                // If it's already boolean, leave as is
            } else {
                // Set defaults
                $data[$field] = ($field === 'is_active') ? true : false;
            }
        }
        // 4. Set default for position
        if (! isset($data['position'])) {
            $data['position'] = 0;
        }
        // 5. Ensure meta_keywords is always an array
        if (! isset($data['meta_keywords']) || ! is_array($data['meta_keywords'])) {
            $data['meta_keywords'] = [];
        }
        // 6. Trim string fields
        $stringFields = ['description', 'icon', 'meta_title', 'meta_description'];
        foreach ($stringFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $trimmed      = trim($data[$field]);
                $data[$field] = $trimmed !== '' ? $trimmed : null;
            }
        }
        // 7. Merge back
        $this->merge($data);
    }

    public function messages(): array {
        return [
            'name.required'           => 'The category name is required.',
            'name.string'             => 'The category name must be a string.',
            'name.max'                => 'The category name may not be greater than 255 characters.',
            'description.string'      => 'The description must be a string.',
            'parent_id.exists'        => 'The selected parent category does not exist.',
            'image.image'             => 'The file must be an image.',
            'image.mimes'             => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image.max'               => 'The image may not be greater than 2048 kilobytes.',
            'icon.string'             => 'The icon must be a string.',
            'icon.max'                => 'The icon may not be greater than 50 characters.',
            'position.integer'        => 'The position must be an integer.',
            'is_active.boolean'       => 'The is_active field must be true or false.',
            'is_featured.boolean'     => 'The is_featured field must be true or false.',
            'meta_title.string'       => 'The meta title must be a string.',
            'meta_title.max'          => 'The meta title may not be greater than 255 characters.',
            'meta_description.string' => 'The meta description must be a string.',
            'meta_keywords.array'     => 'The meta keywords must be an array.',
            'meta_keywords.*.max'     => 'Each meta keyword may not be greater than 100 characters.',

        ];
    }

}
