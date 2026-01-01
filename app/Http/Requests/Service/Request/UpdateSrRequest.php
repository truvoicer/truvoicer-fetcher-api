<?php

namespace App\Http\Requests\Service\Request;

use Truvoicer\TfDbReadCore\Enums\Api\ApiListKey;
use Truvoicer\TfDbReadCore\Enums\FormatOptions;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Repositories\SrRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSrRequest extends FormRequest
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
                'nullable',
                'string',
                Rule::enum(SrType::class)
            ],
            'default_sr' => 'boolean|nullable',
            ApiListKey::LIST_KEY->value => 'string|nullable',
            ApiListKey::LIST_ITEM_REPEATER_KEY->value => 'string|nullable',
            ApiListKey::LIST_FORMAT_OPTIONS->value => [
                'sometimes',
                'array'
            ],
            ApiListKey::LIST_FORMAT_OPTIONS->value .'.*' => [
                'required',
                Rule::enum(FormatOptions::class)
            ],
            ApiListKey::LIST_FORMAT_OPTION_PREG_MATCH->value => [
                'sometimes',
                'string'
            ],
            'name' => 'string|nullable',
            'label' => 'string|nullable',
            'service' => 'integer|nullable',
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
