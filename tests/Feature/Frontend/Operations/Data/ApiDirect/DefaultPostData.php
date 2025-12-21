<?php

namespace Tests\Feature\Frontend\Operations\Data\ApiDirect;

use App\Enums\Api\ApiListKey;
use App\Enums\Api\ApiMethod;
use App\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use App\Enums\Property\PropertyType;
use App\Exceptions\Api\Operation\ApiOperationException;
use App\Exceptions\Api\Response\ApiResponseException;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\TestCase;

class DefaultPostData
{

    static public function labels(): array
    {
        return [
            ['label' => 'Without an ' . ApiListKey::LIST_KEY->value],
            ['label' => 'With post body srConfig, testing in ApiClientHandler'],
            ['label' => 'With body srConfig, testing in ApiClientHandler'],
        ];
    }
    static public function sharedProviderProperties(
        ?ApiMethod $apiMethod = ApiMethod::POST
    ) {
        return [
            [
                'name' => PropertyType::ACCESS_TOKEN->value,
                'value' => '12345'
            ],
            [
                'name' => PropertyType::API_TYPE->value,
                'value' => ApiType::DEFAULT->value
            ],
            [
                'name' => PropertyType::BASE_URL->value,
                'value' => 'http://aurl.com/v1'
            ],
            [
                'name' => PropertyType::RESPONSE_FORMAT->value,
                'value' => ApiResponseFormat::JSON->value
            ],
            [
                'name' => PropertyType::METHOD->value,
                'value' => $apiMethod
            ],
        ];
    }
    static public function providerProperties(): array
    {
        return [
            [
               ...self::sharedProviderProperties()
            ],
            [
               ...self::sharedProviderProperties()
            ],
            [
               ...self::sharedProviderProperties()
            ],
        ];
    }
    static public function srConfigs(): array
    {
        return [
            [
                [
                    'name' => PropertyType::ENDPOINT->value,
                    'value' => '/test-endpoint-1'
                ],
                [
                    'name' => PropertyType::POST_BODY->value,
                    'array_value' => [
                        'sort' => 'title',
                        'direction' => 'asc',
                    ],
                ],
            ],
            [
                [
                    'name' => PropertyType::ENDPOINT->value,
                    'value' => '/test-endpoint-2'
                ],
                [
                    'name' => PropertyType::POST_BODY->value,
                    'array_value' => [
                        'sort' => 'title',
                        'direction' => 'asc',
                    ],
                ],
            ],
            [
                [
                    'name' => PropertyType::ENDPOINT->value,
                    'value' => '/test-endpoint-2'
                ],
                [
                    'name' => PropertyType::BODY->value,
                    'value' => "select * from random where (id = ?);",
                ],
            ],
        ];
    }

    static public function srData(): array
    {
        return [
            [

            ],
            [
               ApiListKey::LIST_KEY->value => 'results'
            ],
            [
               ApiListKey::LIST_KEY->value => 'results'
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
            ],
        ];
    }
    static public function requestResponse(?int $index = null): array
    {
        return [
            [
                'results' => self::responseData()[1]
            ],
            [
                'results' => self::responseData()[$index]
            ],
            [
                'results' => self::responseData()[$index]
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
                'message' => ApiListKey::LIST_KEY->value . ' value is empty.',
                'exception' => ApiResponseException::class
            ],
            [
                'status' => 200,
            ],
            [
                'status' => 200,
            ],
        ];
    }

    static public function partialMocks(?int $index = null)
    {
        return [
            null,
            function (
                MockInterface $mock
            ) use ($index) {

                $srConfigs = self::srConfigs($index)[$index];
                // Create a mocked response object first
                $mockedResponse = Mockery::mock(\Illuminate\Http\Client\Response::class);

                $mockedResponse->shouldReceive('json')
                    ->andReturn(
                        self::requestResponse($index)[$index]
                    );
                $mockedResponse->shouldReceive('failed')
                    ->andReturn(false);

                $mock->shouldReceive('sendDefaultRequest')

                    ->withArgs(function (ApiRequest $request) use ($srConfigs) {
                        $queryIndex = array_search(
                            PropertyType::POST_BODY->value,
                            array_column($srConfigs, 'name')
                        );
                        if ($queryIndex !== false) {
                            $query = $srConfigs[$queryIndex];
                            return $query['array_value'] === $request->getPostBody();
                        }
                        return true;
                    })
                    ->andReturn($mockedResponse);
            },
            function (
                MockInterface $mock
            ) use ($index) {

                $srConfigs = self::srConfigs($index)[$index];
                // Create a mocked response object first
                $mockedResponse = Mockery::mock(\Illuminate\Http\Client\Response::class);

                $mockedResponse->shouldReceive('json')
                    ->andReturn(
                        self::requestResponse($index)[$index]
                    );
                $mockedResponse->shouldReceive('failed')
                    ->andReturn(false);

                $mock->shouldReceive('sendDefaultRequest')

                    ->withArgs(function (ApiRequest $request) use ($srConfigs) {
                        $queryIndex = array_search(
                            PropertyType::BODY->value,
                            array_column($srConfigs, 'name')
                        );
                        if ($queryIndex !== false) {
                            $query = $srConfigs[$queryIndex];
                            return $query['value'] === $request->getBody();
                        }
                        return true;
                    })
                    ->andReturn($mockedResponse);
            },
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
                'partialMocks' => [
                    [
                        'class' => ApiClientHandler::class,
                        'function' => function (
                            MockInterface $mock
                        ) use ($index) {
                            if (self::partialMocks($index)[$index]) {
                                self::partialMocks($index)[$index]($mock);
                            }
                        }
                    ]
                ],

                'callback' => function (
                    TestResponse $response,
                    TestCase $context
                ) {
                    // $context->par
                    // dd($response);
                }
            ];
        }

        return $data;
    }
}
