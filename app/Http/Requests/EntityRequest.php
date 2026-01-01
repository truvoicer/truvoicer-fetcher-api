<?php

namespace App\Http\Requests;

use Truvoicer\TruFetcherGet\Enums\Entity\EntityType;
use Truvoicer\TruFetcherGet\Services\EntityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntityRequest extends FormRequest
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
            "entity" => ["required", "string", Rule::enum(EntityType::class)],
            'ids' => 'array',
        ];
    }
}
