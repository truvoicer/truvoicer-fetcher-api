<?php

namespace App\Http\Requests\Service\Request\ResponseKey;

use App\Enums\Ai\AiClient;
use Truvoicer\TfDbReadCore\Enums\Property\PropertyType;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Truvoicer\TfDbReadCore\Services\Property\PropertyService;
use Truvoicer\TfDbReadCore\Services\Provider\PrioritisedProviderProperty;

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
        $prioritisedProviderProperty = app(PrioritisedProviderProperty::class);
        $responseFormat = $srConfigService->getConfigValue(
            $this->serviceRequest,
            PropertyType::RESPONSE_FORMAT->value
        );

        $prioritisedProviderProperty->buildQuery(
            $prioritisedProviderProperty->preparePrioritisedProviderArray(
                $this->serviceRequest
            ),
            function ($query) {
                return $query->where(
                    'properties.name',
                    PropertyType::RESPONSE_FORMAT->value
                );
            }
        );
        $responseFormat = $prioritisedProviderProperty->getQuery()->first();

        if (!$responseFormat) {
            throw new BadRequestHttpException("Provider properties not found for operation.");
        }
        $responseFormatValue = PropertyService::getPropertyValue(
            $responseFormat->value_type,
            $responseFormat->providerProperty
        );

        $responseFormatErrorMsg = sprintf(
            'Response format property/config is not set or in valid. %s: %s',
            PropertyType::RESPONSE_FORMAT->value,
            $responseFormatValue
        );
        return array_merge(
            array_map(
                fn($data) => ['sometimes', 'string'],
                array_combine(
                    array_column(
                        match ($responseFormatValue) {
                            'json' => DataConstants::REQ_SR_FIELDS_FOR_JSON_POPULATE,
                            'xml' => DataConstants::REQ_SR_FIELDS_FOR_XML_POPULATE,
                            default => throw new BadRequestHttpException(
                                $responseFormatErrorMsg
                            )
                        },
                        'name'
                    ),
                    array_values(
                        match ($responseFormatValue) {
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
                'query' => ['sometimes', 'array', 'nullable'],
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
