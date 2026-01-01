<?php

namespace App\Http\Requests\Admin\Tools\Export;

use Truvoicer\TruFetcherGet\Services\EntityService;
use App\Services\Tools\IExport\ExportService;
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
            'data' => ['required', 'array'],
            'data.*.export_type' => [
                'required',
                'string',
                Rule::in(
                    array_column(ExportService::getExportEntityFields(), 'name')
                )
            ],
            'data.*.export_data' => [
                'required',
                'array',
            ],
        ];
    }
}
