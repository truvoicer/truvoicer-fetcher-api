<?php

namespace App\Http\Requests\Admin\Tools\Export;

use App\Services\EntityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
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
            'categories' => ['sometimes', 'boolean'],
            'categoriesData' => ['sometimes', 'array'],
            'providers' => ['sometimes', 'boolean'],
            'providersData' => ['sometimes', 'array'],
            'services' => ['sometimes', 'boolean'],
            'servicesData' => ['sometimes', 'array'],
            'properties' => ['sometimes', 'boolean'],
            'propertiesData' => ['sometimes', 'array'],
        ];
    }
}
