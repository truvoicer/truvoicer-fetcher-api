<?php

namespace App\Http\Requests\Admin\Tools\Database;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;

class StoreDatabaseIndexRequest extends FormRequest
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
            'keys' => 'required|array',
            'keys.*' => 'required|in:1,-1',
            'sr_type' => [
                'required',
                Rule::enum(SrType::class),
            ],
            'options' => 'nullable|array',
            'options.unique' => 'nullable|boolean',
            'options.name' => 'nullable|string',
        ];
    }
}
