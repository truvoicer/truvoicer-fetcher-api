<?php

namespace Tests\Feature\Frontend\Operations\Data\ApiDirect;

use App\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use App\Enums\Property\PropertyType;
use App\Exceptions\Api\Response\ApiResponseException;

class OpenAiData
{
    static public function labels(): array
    {
        return [
            ['label' => 'OpenAi: Without an items_array_key'],
            ['label' => 'OpenAi: Valid configs'],
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
                    'value' => ApiType::AI_OPEN_AI->value
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
                    'value' => ApiType::AI_OPEN_AI->value
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
            ],
            [
                [
                    'name' => PropertyType::AI_PROMPT->value,
                    'big_text_value' => '12345'
                ],
            ]
        ];
    }

    static public function srData(): array
    {
        return [
            [

            ],
            [
               'items_array_key' => 'root_array'
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
                'message' => 'items_array_key value is empty.',
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
                'afterResponseData' => $afterResponseData
            ];
        }

        return $data;
    }
}
