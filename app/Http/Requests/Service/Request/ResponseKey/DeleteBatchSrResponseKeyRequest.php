<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use Truvoicer\TruFetcherGet\Models\SResponseKey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteBatchSrResponseKeyRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids.*' => Rule::forEach(function () {
                return [
                    Rule::exists(SResponseKey::class, 'id'),
                ];
            })
        ];
    }
}
