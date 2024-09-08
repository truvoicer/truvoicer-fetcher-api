<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
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
            "name" => "string",
            "label" => "string",
            "value_type" => [
                Rule::in(['custom', 'choice'])
            ],
            "value_choices" => [
                'required_if:value_type,choice'
            ],
            "entities" => [
                'required_if:value_type,entity_list'
            ]
        ];
    }
}
