<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceRequestResponseKeyRequest extends FormRequest
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
            'name' => 'required|string',
            'value' => [
                'required',
                'string'
            ],
            'show_in_response' => 'nullable|boolean',
            'list_item' => 'nullable|boolean',
            'append_extra_data' => 'nullable|boolean',
            'append_extra_data_value' => 'string|nullable',
            'prepend_extra_data' => 'nullable|boolean',
            'prepend_extra_data_value' => 'string|nullable',
            'is_service_request' => 'nullable|boolean',
            'has_array_value' => 'nullable|boolean',
            'array_keys' => 'nullable|array',
            'return_data_type' => 'string|nullable|in:text,object,array',
        ];
    }
}
