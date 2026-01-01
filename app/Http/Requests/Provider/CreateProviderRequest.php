<?php

namespace App\Http\Requests\Provider;

use Truvoicer\TruFetcherGet\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|nullable',
            'label' => 'required|string',
            'global' => 'sometimes|boolean',
            'categories' => 'sometimes|array',
            'categories.*.id' => Rule::forEach(function ($value, string $attribute) {
                return [
                    Rule::exists(Category::class, 'id'),
                ];
            }),
        ];
    }
}
