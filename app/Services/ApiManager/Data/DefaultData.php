<?php

namespace App\Services\ApiManager\Data;

use App\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use App\Enums\Entity\EntityType;
use App\Enums\Property\PropertyType;
use App\Models\ProviderProperty;
use App\Models\Sr;
use App\Services\EntityService;

class DefaultData
{
    public const TEST_USER_DATA = [
        'name' => 'Test User',
        'email' => 'test@user.com',
        'password' => 'password',
    ];

    public static function getContentTypeReservedResponseKeys(): array
    {
        $keys = DataConstants::JSON_SERVICE_RESPONSE_KEYS;
        $keys = array_merge(
            $keys,
            array_filter(DataConstants::XML_SERVICE_RESPONSE_KEYS, function ($data, $key) use ($keys) {
                return !array_key_exists($key, array_keys($keys));
            }, ARRAY_FILTER_USE_BOTH)
        );
        return $keys;
    }
    public static function getServiceResponseKeys(array $contentType = ['json']): array
    {
        $keys = DataConstants::SERVICE_RESPONSE_KEYS;
        foreach ($contentType as $type) {
            switch ($type) {
                case 'json':
                    $keys = array_merge(
                        $keys,
                        array_filter(DataConstants::JSON_SERVICE_RESPONSE_KEYS, function ($data, $key) use ($keys) {
                            return !array_key_exists($key, array_keys($keys));
                        }, ARRAY_FILTER_USE_BOTH)
                    );
                    break;
                case 'xml':
                    $keys = array_merge(
                        $keys,
                        array_filter(DataConstants::XML_SERVICE_RESPONSE_KEYS, function ($data, $key) use ($keys) {
                            return !array_key_exists($key, array_keys($keys));
                        }, ARRAY_FILTER_USE_BOTH)
                    );
                    break;
            }
        }

        return $keys;
    }

    public static function getPermissions()
    {
        return [
            'admin' => 'Admin',
            'read' => 'Read',
            'write' => 'Write',
            'update' => 'Update',
            'delete' => 'Delete'
        ];
    }

    public static function getServices()
    {
        return [
            'events-api-service' => 'Events Api Service',
            'retail-api-service' => 'Retail Api Service',
            'crypto-api-service' => 'Crypto Api Services',
            'real-estate-api-service' => 'Real Estate Api Service',
            'recruitment-api-service' => 'Recruitment Api Service',
            'games-api-service' => 'Games Api Service',
            'images-api-service' => 'Images Api Service',
            'video-api-service' => 'Video Api Service',
        ];
    }

    public static function getCategories()
    {
        return [
            'events' => 'Events',
            'retail' => 'Retail',
            'games' => 'Games',
            'crypto' => 'Crypto',
            'real-estate' => 'Real Estate',
            'recruitment' => 'Recruitment'
        ];
    }

    public static function getProviderProperties()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::AI_TEMPERATURE->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "AI System Prompt (E.g. 0.8.)",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::AI_SYSTEM_PROMPT->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "AI System Prompt (E.g. You are a data fetcher etc.)",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT,
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::AI_PROMPT->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "AI Prompt",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT,
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::PROVIDER->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Provider",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_ENTITIES => [EntityType::ENTITY_PROVIDER->value]
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::API_AUTH_TYPE->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Api Authentication Type",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => [
                    DataConstants::OAUTH2,
                    DataConstants::AMAZON_SDK,
                    DataConstants::AUTH_BASIC,
                    DataConstants::AUTH_BEARER,
                    PropertyType::ACCESS_TOKEN->value,
                    DataConstants::AUTH_NONE,
                ]
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::OAUTH_API_AUTH_TYPE->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Api Authentication Type",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => [
                    DataConstants::AUTH_BASIC,
                    DataConstants::AUTH_BEARER,
                    PropertyType::ACCESS_TOKEN->value,
                    DataConstants::AUTH_NONE,
                ]
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::API_TYPE->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Api Type",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ApiType::values()
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::RESPONSE_FORMAT->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Response Format",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ApiResponseFormat::values()
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::BASE_URL->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Base Url",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::USER_ID->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "User Id",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::ACCESS_TOKEN->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Access Token",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::CLIENT_ID->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Client Id",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::CLIENT_SECRET->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Client Secret",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::OAUTH_TOKEN_URL->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Token Url",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::LIST_ITEM_SEARCH_PRIORITY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "List Item Search Priority",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_ENTITIES => array_map(
                    fn(EntityType $entityType) => $entityType->value,
                    EntityType::cases()
                )
            ],
            ...self::getServiceRequestOauthConfig(),
            ...self::getServiceRequestConfig(),
            ...self::getServiceRequestBasicAuthConfig(),
            ...self::getServiceRequestBearerAuthConfig(),
        ];
    }


    public static function getServiceRequestConfig()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::ENDPOINT->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Endpoint',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::METHOD->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Request Method',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::HEADERS->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Headers',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::BODY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Body',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::POST_BODY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Post Body',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::QUERY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Query',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
        ];
    }

    public static function getServiceRequestBasicAuthConfig()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::USERNAME->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Username',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::PASSWORD->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Password',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }

    public static function getServiceRequestBearerAuthConfig()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::BEARER_TOKEN->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Bearer Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }

    public static function getServiceRequestOauthConfig()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_AUTH_TYPE->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Auth Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => 'none',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['none', DataConstants::AUTH_BASIC, DataConstants::AUTH_BEARER, 'auth_token'],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_HEADERS->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Headers',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_BODY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Body',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_POST_BODY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Post Data',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_QUERY->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Query Data',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_METHOD->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Method',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_USERNAME->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Username',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_PASSWORD->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Password',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => PropertyType::TOKEN_REQUEST_TOKEN->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Access Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
}
