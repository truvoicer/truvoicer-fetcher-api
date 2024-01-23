<?php

namespace App\Http\Requests\Service\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSrRequest extends FormRequest
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
            'service' => 'integer|nullable',
            'category' => 'integer|nullable',
        ];
    }
}
