<?php

namespace App\Http\Requests\Service\Request\Parameter;

use Truvoicer\TfDbReadCore\Enums\MbEncoding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceRequestParameterRequest extends FormRequest
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
            'encode_value' => [
                'sometimes',
                'boolean'
            ],
            'encode_from' => [
                'sometimes',
                Rule::enum(MbEncoding::class)
            ],
            'encode_to' => [
                'sometimes',
                Rule::enum(MbEncoding::class)
            ],
        ];
    }
}
