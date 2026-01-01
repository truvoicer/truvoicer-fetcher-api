<?php

namespace App\Http\Requests\Service\Request;

use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Repositories\SrRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateSrRequest extends FormRequest
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
            'id' => [
                'required',
                'integer',
                Rule::exists(Sr::class, 'id'),
            ],
            'parent_sr' => [
                'sometimes',
                'integer',
                Rule::exists(Sr::class, 'id'),
            ],
            'label' => 'required|string',
            'include_child_srs' => 'sometimes|boolean',
        ];
    }
}
