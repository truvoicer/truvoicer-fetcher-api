<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Models\Provider;
use App\Models\SrResponseKey;
use App\Models\SrResponseKeySr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSrResponseKeySrRequest extends FormRequest
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
            'response_key_id' => [
                'required',
                'integer',
                Rule::exists(SrResponseKey::class, 'id'),
            ],
            'response_key_sr' => [
                'required',
                'array'
            ],
            'response_key_sr.action' => [
                'required',
                'string'
            ],
            'response_key_sr.single_request' => [
                'required',
                'boolean'
            ],
            'response_key_sr.disable_request' => [
                'required',
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
                'required',
                'integer'
            ],

        ];
    }
}
