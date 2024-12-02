<?php

namespace App\Http\Requests\Service\Request\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSrScheduleRequest extends FormRequest
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
            'execute_immediately' => 'boolean|nullable',
            'execute_immediately_choice' => 'required_if:execute_immediately,true|string',
            'forever' => 'boolean|nullable',
            'disabled' => 'boolean|nullable',
            'disable_child_srs' => 'boolean|nullable',
            'priority' => 'integer|nullable',
            'has_start_date' => 'boolean|nullable',
            'start_date' => 'date|nullable',
            'has_end_date' => 'boolean|nullable',
            'end_date' => 'date|nullable',
            'use_cron_expression' => 'boolean|nullable',
            'cron_expression' => 'string|nullable',
            'every_minute' => 'boolean|nullable',
            'minute' => 'integer|nullable',
            'every_hour' => 'boolean|nullable',
            'hour' => 'integer|nullable',
            'every_day' => 'boolean|nullable',
            'day' => 'integer|nullable',
            'every_weekday' => 'boolean|nullable',
            'weekday' => 'integer|nullable',
            'every_month' => 'boolean|nullable',
            'month' => 'integer|nullable',
            'parameters' => 'array|nullable'
        ];
    }
}
