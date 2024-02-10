<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Models\Sr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestResponseKeyRequest extends FormRequest
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
            'name' => 'nullable|string',
            'value' => [
                'nullable',
                'string'
            ],
            'show_in_response' => 'nullable|boolean',
            'list_item' => 'nullable|boolean',
            'custom_value' => 'nullable|boolean',
            'append_extra_data_value' => 'string|nullable',
            'prepend_extra_data_value' => 'string|nullable',
            'is_service_request' => 'nullable|boolean',
            'array_keys' => 'nullable|array',
            'response_key_srs.*' => Rule::forEach(function ($value, string $attribute) {
                return [
                    'required_if_accepted:is_service_request',
                    Rule::exists(Sr::class, 'id'),
                ];
            })
        ];
    }
}
