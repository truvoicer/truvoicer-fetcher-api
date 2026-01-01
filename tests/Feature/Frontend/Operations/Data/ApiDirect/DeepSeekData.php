<?php

namespace Tests\Feature\Frontend\Operations\Data\ApiDirect;

use Truvoicer\TfDbReadCore\Enums\Api\ApiListKey;
use Truvoicer\TfDbReadCore\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use Truvoicer\TfDbReadCore\Enums\Property\PropertyType;
use Truvoicer\TfDbReadCore\Exceptions\Api\Response\ApiResponseException;
use Illuminate\Testing\TestResponse;

class DeepSeekData
{
    static public function labels(): array
    {
        return [
            ['label' => 'DeepSeek: Without an ' . ApiListKey::LIST_KEY->value],
            ['label' => 'DeepSeek: Valid configs'],
        ];
    }
    static public function srData(): array
    {
        return [
            [

            ],
            [
               ApiListKey::LIST_KEY->value => 'root_array'
            ]
        ];
    }
    static public function providerProperties(): array
    {
        return [
            [
                [
                    'name' => PropertyType::ACCESS_TOKEN->value,
                    'value' => '12345'
                ],
                [
                    'name' => PropertyType::API_TYPE->value,
                    'value' => ApiType::AI_DEEP_SEEK->value
                ],
                [
                    'name' => PropertyType::BASE_URL->value,
                    'value' => 'http://aurl.com/v1'
                ],
                [
                    'name' => PropertyType::RESPONSE_FORMAT->value,
                    'value' => ApiResponseFormat::JSON->value
                ],
            ],
            [
                [
                    'name' => PropertyType::ACCESS_TOKEN->value,
                    'value' => '12345'
                ],
                [
                    'name' => PropertyType::API_TYPE->value,
                    'value' => ApiType::AI_DEEP_SEEK->value
                ],
                [
                    'name' => PropertyType::BASE_URL->value,
                    'value' => 'http://aurl.com/v1'
                ],
                [
                    'name' => PropertyType::RESPONSE_FORMAT->value,
                    'value' => ApiResponseFormat::JSON->value
                ],
            ]
        ];
    }
    static public function srConfigs(): array
    {
        return [
            [
                [
                    'name' => PropertyType::AI_PROMPT->value,
                    'big_text_value' => '12345'
                ],
                [
                    'name' => PropertyType::AI_SYSTEM_PROMPT->value,
                    'big_text_value' => '1234567789'
                ],
            ],
            [
                [
                    'name' => PropertyType::AI_PROMPT->value,
                    'big_text_value' => '12345'
                ],
                [
                    'name' => PropertyType::AI_SYSTEM_PROMPT->value,
                    'big_text_value' => '1234567789'
                ],
            ]
        ];
    }
    static public function srResponseKeys(): array
    {
        return [
            [
                [
                    'name' => 'id',
                    'value' => 'id'
                ],
                [
                    'name' => 'name',
                    'value' => 'name'
                ],
                [
                    'name' => 'title',
                    'value' => 'title'
                ],
                [
                    'name' => 'description',
                    'value' => 'description'
                ],
            ],
            [
                [
                    'name' => 'id',
                    'value' => 'id',
                    'show_in_response' => true,
                    'list_item' => true
                ],
                [
                    'name' => 'name',
                    'value' => 'name',
                    'show_in_response' => true,
                    'list_item' => true
                ],
                [
                    'name' => 'title',
                    'value' => 'title',
                    'show_in_response' => true,
                    'list_item' => true
                ],
                [
                    'name' => 'description',
                    'value' => 'description',
                    'show_in_response' => true,
                    'list_item' => true
                ],
            ]
        ];
    }
    static public function requestResponse(?int $index = null): array
    {
        return [
            [
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(self::responseData()[$index])
                        ]
                    ]
                ]

            ],
            [
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(self::responseData()[$index])
                        ]
                    ]
                ]

            ],
        ];
    }
    static public function responseData(): array
    {
        return [
            [],
            [
                [
                    'id' => 1,
                    'name' => 'test-name',
                    'title' => 'Test Title',
                    'description' => 'This is a test description for test title'
                ],
                [
                    'id' => 2,
                    'name' => 'test-name-2',
                    'title' => 'Test Title 2',
                    'description' => 'This is a test description for test title 2'
                ],
                [
                    'id' => 3,
                    'name' => 'test-name-3',
                    'title' => 'Test Title 3',
                    'description' => 'This is a test description for test title 3'
                ],
            ],
        ];
    }

    static public function afterResponse()
    {
        return [
            [
                'status' => 400,
                'message' => ApiListKey::LIST_KEY->value .' value is empty.',
                'exception' => ApiResponseException::class
            ],
            [
                'status' => 200,
                'message' => '',
            ],
        ];
    }


    static public function data(?array $indexes = []): array
    {
        $data = [];

        foreach (self::providerProperties() as $index => $providerProperty) {
            if (count($indexes) && !in_array($index, $indexes)) {
                continue;
            }

            $srData = self::srData($index)[$index];
            $srConfigs = self::srConfigs($index)[$index];
            $srResponseKeys = self::srResponseKeys($index)[$index];
            $requestResponse = self::requestResponse($index)[$index];
            $responseData = self::responseData($index)[$index];
            $afterResponseData = self::afterResponse($index)[$index];
            $data[self::labels()[$index]['label']] = [
                'srData' => $srData,
                'properties' => $providerProperty,
                'srConfigs' => $srConfigs,
                'srResponseKeys' => $srResponseKeys,
                'requestResponse' => $requestResponse,
                'responseData' => $responseData,
                'afterResponseData' => $afterResponseData,
                'callback' => function (TestResponse $response) {

                }
            ];
        }

        return $data;
    }
}
