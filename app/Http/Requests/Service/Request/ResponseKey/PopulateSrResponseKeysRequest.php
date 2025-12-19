<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Enums\Ai\AiClient;
use App\Enums\Property\PropertyType;
use App\Models\Sr;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
    public function rules(SrConfigService $srConfigService): array
    {
        $responseFormat = $srConfigService->getConfigValue(
            $this->serviceRequest,
            PropertyType::RESPONSE_FORMAT->value
        );
        $responseFormatErrorMsg = sprintf(
            'Response format property/config is not set or in valid. %s: %s',
            PropertyType::RESPONSE_FORMAT->value,
            $responseFormat
        );
        return array_merge(
            array_map(
                fn($data) => ['sometimes', 'string'],
                array_combine(
                    array_column(
                        match ($responseFormat) {
                            'json' => DataConstants::REQ_SR_FIELDS_FOR_JSON_POPULATE,
                            'xml' => DataConstants::REQ_SR_FIELDS_FOR_XML_POPULATE,
                            default => throw new BadRequestHttpException(
                                $responseFormatErrorMsg
                            )
                        },
                        'name'
                    ),
                    array_values(
                        match ($responseFormat) {
                            'json' => DataConstants::REQ_SR_FIELDS_FOR_JSON_POPULATE,
                            'xml' => DataConstants::REQ_SR_FIELDS_FOR_XML_POPULATE,
                            default => throw new BadRequestHttpException(
                                $responseFormatErrorMsg
                            )
                        }
                    )
                )
            ),
            [
                'query' => ['sometimes', 'array'],
                'overwrite' => ['sometimes', 'boolean'],
                'enable_ai' => ['sometimes', 'boolean'],
                'ai_clients' => ['sometimes', 'array'],
                'ai_clients.*' => [
                    'required',
                    Rule::enum(AiClient::class)
                ],
                'srs.*' => Rule::forEach(function ($value, string $attribute) {
                    return [
                        Rule::exists(Sr::class, 'id'),
                    ];
                }),
            ]
        );
    }
}
