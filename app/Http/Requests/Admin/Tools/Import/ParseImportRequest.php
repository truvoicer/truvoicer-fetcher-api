<?php

namespace App\Http\Requests\Admin\Tools\Import;

use App\Services\EntityService;
use App\Services\Tools\IExport\ExportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParseImportRequest extends FormRequest
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
            'upload_file' => ['required', 'file', 'mimetypes:application/json'],
        ];
    }
}
