<?php

namespace App\Http\Requests\Provider\Property;

use App\Services\ApiManager\Data\DataConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProviderPropertyRequest extends FormRequest
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
            'value' => [
                'string',
                'nullable',
                Rule::requiredIf(fn () => in_array($this->get('value_type'), ['text', 'choice']))
            ],
            "value_type" => [
                Rule::in(DataConstants::REQUEST_CONFIG_VALUE_TYPES)
            ],
            "big_text_value" => [
                'string',
                'nullable',
                'required_if:value_type,big_text'
            ],
            "array_value" => [
                'array',
                'nullable',
                'required_if:value_type,list,entity_list'
            ]
        ];
    }
}
