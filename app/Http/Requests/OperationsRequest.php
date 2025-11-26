<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ApiFetchType;
use App\Enums\DatabaseFilterType;
use Illuminate\Validation\Validator;

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
            'api_fetch_type' => ['required', 'string', Rule::in(['database', 'api_direct', 'mixed'])],
            'page_number' => ['sometimes', 'integer', 'nullable'],
            'page_size' => ['sometimes', 'integer', 'nullable'],
            'sort_by' => ['sometimes', 'string', 'nullable'],
            'sort_order' => ['sometimes', 'string', 'nullable'],
            'date_key' => ['sometimes', 'string', 'nullable'],
            'provider' =>['sometimes', 'array', 'nullable'],
            'service' => ['sometimes', 'string', 'nullable'],
            'item_id' => ['sometimes', 'nullable'],
            'filters' => [
                'sometimes',
                'array',
            ],
            'filters.*.type' => [
                'required',
                Rule::enum(DatabaseFilterType::class),
            ],
            'filters.*.field' => [
                'required',
                'string',
                'max:255'
            ],
            'filters.*.value' => [
                'nullable', // Allows it to be present as 'null' initially
            ],
            'positions' => [
                'sometimes',
                'array'
            ],
            'positions.start' => [
                'sometimes',
                'array'
            ],
            'positions.start.*.document_id' => [
                'required',
                'string'
            ],
            'positions.end' => [
                'sometimes',
                'array'
            ],
            'positions.end.*.document_id' => [
                'required',
                'string'
            ],
            'positions.custom' => [
                'sometimes',
                'array'
            ],
            'positions.custom.*.document_id' => [
                'required',
                'string'
            ],
            'positions.custom.*.insert_index' => [
                'required',
                'integer'
            ],
        ];

    }

    /**
     * Get the validated data from the request.
     * * @param  string|null  $key
     * @param  mixed  $default
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        // 1. Call the parent method to execute validation and get the full validated array.
        // This is the array that is incorrectly including the null values.
        $validatedData = parent::validated();

        // 2. Check and filter the filters key.
        if (isset($validatedData['filters'])) {

            // Filter out any request_filter where the 'value' is effectively empty (null or "").
            $cleanedFilters = array_filter($validatedData['filters'], function ($filter) {
                $value = $filter['value'] ?? null;

                // This condition keeps items if the value is 0 (valid) OR if the value
                // is not null AND not an empty string.
                return ($value === 0) || (!is_null($value) && $value !== '');
            });

            // Update the validated data with the cleaned, re-indexed array.
            $validatedData['filters'] = array_values($cleanedFilters);
        }

        // 3. Return the fully filtered data.
        return $validatedData;
    }
    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // Get the answers array from the request, default to empty array if not present
            $answers = $this->input('filters', []);

            foreach ($answers as $index => $answer) {
                // Get the type for the current answer item
                $type = data_get($answer, 'type');

                // Start with the base rules that apply to all values
                $valueRules = ['required'];

                // Add specific rules based on the QuestionType
                switch ($type) {
                    case DatabaseFilterType::BOOLEAN->value:
                        $valueRules = array_merge($valueRules, [
                            'boolean',
                        ]);
                        break;

                    case DatabaseFilterType::DATE->value:
                        $valueRules = array_merge($valueRules, [
                            'date',
                        ]);
                        break;

                    case DatabaseFilterType::INTEGER->value:
                        $valueRules = array_merge($valueRules, [
                            'integer',
                        ]);
                        break;

                    case DatabaseFilterType::STRING->value:
                        $valueRules = array_merge($valueRules, [
                            'string',
                            'max:255'
                        ]);
                        break;
                    case DatabaseFilterType::GREATER_THAN->value:
                        $valueRules = array_merge($valueRules, [
                            'integer',
                        ]);
                        break;
                    case DatabaseFilterType::LESS_THAN->value:
                        $valueRules = array_merge($valueRules, [
                            'integer',
                        ]);
                        break;
                }

                $validator->addRules(["filters.$index.value" => $valueRules]);
            }
        });
    }
}
