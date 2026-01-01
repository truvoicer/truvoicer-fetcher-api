<?php

namespace App\Http\Requests\Admin\Tools\Import;

use Truvoicer\TfDbReadCore\Services\EntityService;
use App\Services\Tools\IExport\ExportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportMappingsRequest extends FormRequest
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
            'mappings' => ['required', 'array'],
            'mappings.*.mapping' => [
                'required',
                'array',
            ],
            'mappings.*.id' => [
                'sometimes',
                'integer',
            ],
            'mappings.*.name' => [
                'sometimes',
                'string',
            ],
            'mappings.*.label' => [
                'sometimes',
                'string',
            ],
            'mappings.*.root' => [
                'sometimes',
                'boolean',
            ],
            'mappings.*.action' => [
                'sometimes',
                'string',
            ],
            'mappings.*.import_type' => [
                'sometimes',
                'string',
            ],
            'mappings.*.children' => [
                'sometimes',
                'array',
            ],
            'mappings.*.dest' => [
                'sometimes',
                'array',
            ],
            'file_id' => ['required', 'integer', 'exists:files,id'],
        ];
    }
}
