<?php

namespace App\Http\Requests\Service\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSrRequest extends FormRequest
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
            'type' => 'string|required',
            'default_sr' => 'boolean|nullable',
            'name' => 'string|nullable',
            'label' => 'required|string',
            'service' => 'required|integer',
            'category' => 'integer|nullable',
            'pagination_type' => [
                'nullable',
                'string',
                Rule::in(['page', 'offset'])
            ],
            'query_parameters' => 'array|nullable',
            'default_data' => 'array|nullable',
        ];
    }
}
