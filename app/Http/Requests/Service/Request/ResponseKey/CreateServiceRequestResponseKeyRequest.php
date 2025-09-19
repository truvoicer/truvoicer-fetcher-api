<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Models\Sr;
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
            'searchable' => 'nullable|boolean',
            'list_item' => 'nullable|boolean',
            'custom_value' => 'nullable|boolean',
            'is_date' => 'nullable|boolean',
            'date_format' => 'nullable|string',
            'append_extra_data_value' => 'string|nullable',
            'prepend_extra_data_value' => 'string|nullable',
            'array_keys' => 'nullable|array',
        ];
    }
}
