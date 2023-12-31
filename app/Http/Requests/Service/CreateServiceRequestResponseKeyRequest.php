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
                'string',
                'nullable',
                Rule::requiredIf(fn () => in_array($this->get('value_type'), ['text', 'choice']))
            ],
            "value_type" => [
                Rule::in(['list', 'text', 'choice'])
            ],
            "array_value" => [
                'array',
                'nullable',
                'required_if:value_type,list'
            ]
        ];
    }
}
