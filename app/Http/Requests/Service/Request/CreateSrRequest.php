<?php

namespace App\Http\Requests\Service\Request;

use App\Enums\Sr\SrType;
use App\Models\Sr;
use App\Repositories\SrRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSrRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::enum(SrType::class)
            ],
            'default_sr' => 'boolean|nullable',
            'items_array_key' => 'string|nullable',
            'item_repeater_key' => 'string|nullable',
            'name' => 'string|nullable',
            'label' => 'required|string',
            'service' => 'required|integer',
            'category' => 'integer|nullable',
            'pagination_type' => [
                'nullable',
                'string',
                Rule::in(['page', 'offset'])
            ],
            'query_parameters' => 'array|nullable',
            'default_data' => 'array|nullable',
            'parent_sr' => [
                'sometimes',
                'integer',
                Rule::exists(Sr::class, 'id'),
            ],
        ];
    }
}
