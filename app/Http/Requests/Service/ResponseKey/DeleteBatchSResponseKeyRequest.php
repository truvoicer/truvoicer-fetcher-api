<?php

namespace App\Http\Requests\Service\ResponseKey;

use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\S;
use Truvoicer\TfDbReadCore\Models\SResponseKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids.*' => Rule::forEach(function ($value, string $attribute) {
                return [
                    Rule::exists(SResponseKey::class, 'id'),
                ];
            })
        ];
    }
}
