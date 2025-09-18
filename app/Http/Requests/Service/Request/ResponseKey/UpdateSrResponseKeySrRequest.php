<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Models\Provider;
use App\Models\SrResponseKeySr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSrResponseKeySrRequest extends FormRequest
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
            'response_key' => [
                'sometimes',
                'integer',
                Rule::exists(SrResponseKeySr::class, 'id'),
            ],
            'response_key_sr' => [
                'required',
                'array'
            ],
            'response_key_sr.action' => [
                'sometimes',
                'string'
            ],
            'response_key_sr.single_request' => [
                'sometimes',
                'boolean'
            ],
            'response_key_sr.disable_request' => [
                'sometimes',
                'boolean'
            ],
            'response_key_sr.response_response_keys' => [
                'sometimes',
                'nullable',
                'array'
            ],
            'response_key_sr.request_response_keys' => [
                'sometimes',
                'nullable',
                'array'
            ],
            'response_key_sr.id' => [
                'sometimes',
                'integer'
            ],
        ];
    }
}
