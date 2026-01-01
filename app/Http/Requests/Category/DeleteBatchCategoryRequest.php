<?php

namespace App\Http\Requests\Category;

use Truvoicer\TfDbReadCore\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteBatchCategoryRequest extends FormRequest
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
                    Rule::exists(Category::class, 'id'),
                ];
            })
        ];
    }
}
