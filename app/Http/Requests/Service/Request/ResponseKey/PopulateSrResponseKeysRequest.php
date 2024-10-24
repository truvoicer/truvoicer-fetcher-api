<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Models\Sr;
use App\Services\ApiManager\Data\DefaultData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PopulateSrResponseKeysRequest extends FormRequest
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
        $reservedContentTypeKeys = DefaultData::getContentTypeReservedResponseKeys();
        return array_merge(
            array_map(
                fn($data) => ['required', 'string'],
                array_combine(
                    array_column($reservedContentTypeKeys, 'name'),
                    array_values($reservedContentTypeKeys)
                )
            ),
            [
                'overwrite' => ['sometimes', 'boolean'],
                'srs.*' => Rule::forEach(function ($value, string $attribute) {
                    return [
                        Rule::exists(Sr::class, 'id'),
                    ];
                }),
            ]
        );
    }
}
