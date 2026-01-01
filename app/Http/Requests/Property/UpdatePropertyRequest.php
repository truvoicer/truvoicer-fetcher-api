<?php

namespace App\Http\Requests\Property;

use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
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
                Rule::in(DataConstants::REQUEST_CONFIG_VALUE_TYPES)
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
