<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderRequest extends FormRequest
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
            'name' => 'string|nullable',
            'label' => 'string|nullable',
            'api_base_url' => 'string|nullable',
            'access_key' => 'string|nullable',
            'secret_key' => 'string|nullable',
            'user_id' => 'string|nullable',
            'categories' => 'array|nullable',
        ];
    }
}
