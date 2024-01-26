<?php

namespace App\Library\Defaults;

use App\Services\ApiManager\ApiBase;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\ApiServices\ServiceRequests\SrConfigService;

class DefaultData
{
    public const TEST_USER_DATA = [
        'name' => 'Test User',
        'email' => 'test@user.com',
        'password' => 'password',
    ];
    public const REQUEST_KEYS = [
        "POST_PER_PAGE" => "posts_per_page",
    ];
    public const SERVICE_RESPONSE_KEYS = [
        "ITEMS_ARRAY" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "items_array",
        ],
        "TOTAL_ITEMS" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "total_items"
        ],
        "TOTAL_PAGES" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "total_pages"
        ],
        "PAGE_SIZE" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "page_size"
        ],
        "PAGE_NUMBER" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "page_number"
        ],
        "OFFSET" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "offset"
        ],
        "ITEM_ID" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "item_id"
        ],
    ];
    public const XML_SERVICE_RESPONSE_KEYS = [
        "ITEM_REPEATER_KEY" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => "item_repeater_key"
        ],
    ];
    public static function getServiceResponseKeys(string $contentType = 'json')
    {
        $keys = self::SERVICE_RESPONSE_KEYS;
        if ($contentType === 'xml') {
            $keys = array_merge($keys, self::XML_SERVICE_RESPONSE_KEYS);
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
                'name' => "api_authentication_type",
                'label' => "Api Authentication Type",
                'value_type' => 'choice',
                'value_choices' => [
                    ApiBase::OAUTH_BASIC,
                    ApiBase::OAUTH,
                    ApiBase::OAUTH_BEARER,
                    ApiBase::OAUTH_BODY,
                    ApiBase::AMAZON_SDK,
                    ApiBase::AUTH_BASIC,
                    ApiBase::AUTH_BEARER,
                    ApiBase::ACCESS_TOKEN,
                    ApiBase::AUTH_NONE,
                ]
            ],
            [
                'name' => "api_type",
                'label' => "Api Type",
                'value_type' => 'choice',
                'value_choices' => [
                    'query_string',
                    'query_schema'
                ]
            ],
            [
                'name' => "oauth_access_token_grant_type",
                'label' => "Oauth Access Token Grant Type",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "oauth_grant_type_field_name",
                'label' => "Oauth Grant Type Field Name",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "oauth_grant_type_field_value",
                'label' => "Oauth Grant Type Field value",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "oauth_scope_field_name",
                'label' => "Oauth Scope Field Name",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "oauth_scope_field_value",
                'label' => "Oauth Scope Field Value",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "oauth_token_url",
                'label' => "Oauth Token Url",
                'value_type' => 'custom',
                'value_choices' => null
            ],
        ];
    }


    public static function getServiceRequestConfig()
    {
        return [
            [
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'endpoint',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'request_method',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
            ],
            [
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'headers',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                SrConfigService::REQUEST_CONFIG_ITEM_REQUIRED => true,
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'content_type',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => 'json',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['json', 'xml'],
            ]
        ];
    }
    public static function getServiceRequestBasicAuthConfig()
    {
        return [
            [
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'username',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'password',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
    public static function getServiceRequestBearerAuthConfig()
    {
        return [
            [
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'bearer_token',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
}
