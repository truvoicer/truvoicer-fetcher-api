<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperationsRequest extends FormRequest
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
            "page_number" => ["sometimes", "integer", 'nullable'],
            "page_size" => ["sometimes", "integer", 'nullable'],
            "sort_by" => ["sometimes", "string", 'nullable'],
            "sort_order" => ["sometimes", "string", 'nullable'],
            "date_key" => ["sometimes", "string", 'nullable'],
            "provider" =>["sometimes", "array", 'nullable'],
            "service" => ["sometimes", "string", 'nullable'],
            "item_id" => ["sometimes", 'nullable'],
        ];
    }
}
