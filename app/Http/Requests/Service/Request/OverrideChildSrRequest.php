<?php

namespace App\Http\Requests\Service\Request;

use Truvoicer\TruFetcherGet\Models\SrChildSr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OverrideChildSrRequest extends FormRequest
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
            "key" => [
                'required',
                Rule::in(SrChildSr::FIELDS)
            ],
            "value" => "boolean|required"
        ];
    }
}
