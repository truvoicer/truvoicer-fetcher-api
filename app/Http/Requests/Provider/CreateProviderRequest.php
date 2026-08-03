<?php

namespace App\Http\Requests\Provider;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Truvoicer\TfDbReadCore\Models\Category;

class CreateProviderRequest extends FormRequest
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
            'name' => 'string|nullable',
            'label' => 'required|string',
            'global' => 'sometimes|boolean',
            'categories' => 'sometimes|array',
            'categories.*.id' => [
                'integer',
                Rule::exists(Category::class, 'id'),
            ],
        ];
    }
}
