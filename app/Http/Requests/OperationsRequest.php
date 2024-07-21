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
            "page_number" => ["sometimes", "integer"],
            "page_size" => ["sometimes", "integer"],
            "sort_by" => ["sometimes", "string"],
            "sort_order" => ["sometimes", "string"],
            "date_key" => ["sometimes", "string"],
            "provider" =>["sometimes", "array"],
            "service" => ["sometimes", "string"],
        ];
    }
}
