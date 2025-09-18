<?php

namespace App\Http\Requests\User\Setting;

use App\Enums\User\Setting\OpenMode;
use App\Enums\User\Setting\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSettingRequest extends FormRequest
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
            'theme' => [
                'sometimes',
                Rule::enum(Theme::class)
            ],
            'open_mode' => [
                'sometimes',
                Rule::enum(OpenMode::class)
            ],
        ];
    }
}
