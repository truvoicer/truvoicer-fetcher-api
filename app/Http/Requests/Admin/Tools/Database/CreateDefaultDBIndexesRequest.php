<?php

namespace App\Http\Requests\Admin\Tools\Database;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;

class CreateDefaultDBIndexesRequest extends FormRequest
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
            'sr_type' => [
                'required',
                Rule::enum(SrType::class),
            ],
        ];
    }
}
