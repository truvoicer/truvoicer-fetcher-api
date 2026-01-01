<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\SrResponseKey;
use Truvoicer\TruFetcherGet\Models\SrResponseKeySr;
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
            'sr_response_key_id' => [
                'required',
                'integer',
                Rule::exists(SrResponseKey::class, 'id'),
            ],
            'action' => [
                'required',
                'string'
            ],
            'single_request' => [
                'required',
                'boolean'
            ],
            'disable_request' => [
                'required',
                'boolean'
            ],
            'response_response_keys' => [
                'sometimes',
                'nullable',
                'array'
            ],
            'request_response_keys' => [
                'sometimes',
                'nullable',
                'array'
            ],
            'sr_id' => [
                'required',
                'integer'
            ],

        ];
    }
}
