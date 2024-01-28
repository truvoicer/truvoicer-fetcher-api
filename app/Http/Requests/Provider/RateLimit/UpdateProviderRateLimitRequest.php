<?php

namespace App\Http\Requests\Provider\RateLimit;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderRateLimitRequest extends FormRequest
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
            'max_attempts' => 'integer|nullable',
            'decay_seconds' => 'integer|nullable',
            'delay_seconds_per_request' => 'integer|nullable',
        ];
    }
}
