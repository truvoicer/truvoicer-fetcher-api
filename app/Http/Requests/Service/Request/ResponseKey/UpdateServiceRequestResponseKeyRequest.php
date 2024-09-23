<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Models\Sr;
use App\Repositories\SrResponseKeySrRepository;
use App\Rules\StringOrInteger;
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
            'searchable' => 'nullable|boolean',
            'list_item' => 'nullable|boolean',
            'custom_value' => 'nullable|boolean',
            'is_date' => 'nullable|boolean',
            'date_format' => 'nullable|string',
            'append_extra_data_value' => 'string|nullable',
            'prepend_extra_data_value' => 'string|nullable',
            'is_service_request' => 'nullable|boolean',
            'array_keys' => 'nullable|array',
            'response_key_srs.*.action' => [
                'required',
                Rule::in(SrResponseKeySrRepository::ALLOWED_ACTIONS)
            ],
            'response_key_srs.*.single_request' => [
                'boolean'
            ],
            'response_key_srs.*.disable_request' => [
                'boolean'
            ],
            'response_key_srs.*.id' => Rule::forEach(function ($value, string $attribute) {
                return [
                    'required_if_accepted:is_service_request',
                    Rule::exists(Sr::class, 'id'),
                ];
            }),
            'response_key_srs.*.request_response_keys.*' => Rule::forEach(function ($value, string $attribute) {
                return [
                    'string'
                ];
            }),
            'response_key_srs.*.response_response_keys.*' => Rule::forEach(function ($value, string $attribute) {
                return [
                    'string'
                ];
            })
        ];
    }
}
