<?php

namespace App\Http\Requests\Service\ResponseKey;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Truvoicer\TfDbReadCore\Models\SResponseKey;

class DeleteBatchSResponseKeyRequest extends FormRequest
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
            'ids' => ['required', 'array'],
            'ids.*' => [
                'integer',
                Rule::exists(SResponseKey::class, 'id'),
            ],
        ];
    }
}
