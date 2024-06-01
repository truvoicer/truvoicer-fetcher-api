<?php

namespace App\Services\ApiManager\Data;

use App\Services\ApiServices\SResponseKeysService;

class DefaultData
{
    public const TEST_USER_DATA = [
        'name' => 'Test User',
        'email' => 'test@user.com',
        'password' => 'password',
    ];

    public static function getServiceResponseKeys(string $contentType = 'json')
    {
        $keys = DataConstants::SERVICE_RESPONSE_KEYS;
        if ($contentType === 'xml') {
            $keys = array_merge($keys, DataConstants::XML_SERVICE_RESPONSE_KEYS);
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
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::API_AUTH_TYPE,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Api Authentication Type",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'choice',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => [
                    DataConstants::OAUTH2,
                    DataConstants::AMAZON_SDK,
                    DataConstants::AUTH_BASIC,
                    DataConstants::AUTH_BEARER,
                    DataConstants::ACCESS_TOKEN,
                    DataConstants::AUTH_NONE,
                ]
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::API_TYPE,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Api Type",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'choice',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => [
                    'query_string',
                    'query_schema'
                ]
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::BASE_URL,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Base Url",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::USER_ID,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "User Id",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::ACCESS_TOKEN,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Access Token",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::CLIENT_ID,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Client Id",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::CLIENT_SECRET,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Client Secret",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::OAUTH_GRANT_TYPE,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Access Token Grant Type",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::OAUTH_GRANT_TYPE_FIELD_NAME,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Grant Type Field Name",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::OAUTH_GRANT_TYPE_FIELD_VALUE,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Grant Type Field value",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::OAUTH_SCOPE_FIELD_NAME,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Scope Field Name",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::OAUTH_SCOPE_FIELD_VALUE,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Scope Field Value",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::OAUTH_TOKEN_URL,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => "Oauth Token Url",
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => 'text',
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => null
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
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'endpoint',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Endpoint',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'request_method',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Request Method',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'headers',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Headers',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'content_type',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Content Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => 'json',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['json', 'xml'],
            ]
        ];
    }
    public static function getServiceRequestBasicAuthConfig()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'username',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Username',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'password',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Password',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
    public static function getServiceRequestBearerAuthConfig()
    {
        return [
            [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => 'bearer_token',
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Bearer Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
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
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_AUTH_TYPE,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Auth Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => 'none',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['none', DataConstants::AUTH_BASIC, DataConstants::AUTH_BEARER, 'auth_token'],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_HEADERS,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Headers',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_BODY,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Post Data',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_QUERY,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Query Data',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_METHOD,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Method',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_USERNAME,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Username',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_PASSWORD,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Password',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_NAME => DataConstants::TOKEN_REQUEST_TOKEN,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Oauth Token Request Access Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => '',
                DataConstants::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
}
