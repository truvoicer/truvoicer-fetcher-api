<?php

namespace App\Http\Requests\Provider\RateLimit;

use Truvoicer\TfDbReadCore\Models\ProviderRateLimit;
use Truvoicer\TfDbReadCore\Models\SrRateLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteBatchProviderRateLimitRequest extends FormRequest
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
                    Rule::exists(ProviderRateLimit::class, 'id'),
                ];
            })
        ];
    }
}
