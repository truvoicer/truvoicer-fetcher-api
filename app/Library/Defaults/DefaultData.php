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

    public const ITEMS_ARRAY = 'items_array';
    public const TOTAL_ITEMS = 'total_items';
    public const TOTAL_PAGES = 'total_pages';
    public const PAGE_SIZE = 'page_size';
    public const PAGE_NUMBER = 'page_number';
    public const OFFSET = 'offset';
    public const ITEM_ID = 'item_id';
    public const PREV_PAGE = 'prev_page';
    public const NEXT_PAGE = 'next_page';
    public const LAST_PAGE = 'last_page';
    public const PAGINATION_TYPE = 'pagination_type';

    public const SERVICE_RESPONSE_KEYS = [
        "ITEMS_ARRAY" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::ITEMS_ARRAY,
        ],
        "TOTAL_ITEMS" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::TOTAL_ITEMS,
        ],
        "TOTAL_PAGES" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::TOTAL_PAGES,
        ],
        "PAGE_SIZE" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::PAGE_SIZE,
        ],
        "PAGE_NUMBER" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::PAGE_NUMBER,
        ],
        "OFFSET" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::OFFSET,
        ],
        "ITEM_ID" => [
            SResponseKeysService::RESPONSE_KEY_REQUIRED => true,
            SResponseKeysService::RESPONSE_KEY_NAME => self::ITEM_ID,
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
                'name' => "base_url",
                'label' => "Base Url",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "user_id",
                'label' => "User Id",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "access_token",
                'label' => "Access Token",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "client_id",
                'label' => "Client Id",
                'value_type' => 'custom',
                'value_choices' => null
            ],
            [
                'name' => "client_secret",
                'label' => "Client Secret",
                'value_type' => 'custom',
                'value_choices' => null
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
    public static function getServiceRequestOauthConfig()
    {
        return [
            [
                SrConfigService::REQUEST_CONFIG_ITEM_REQUIRED => true,
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'token_request_headers',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                SrConfigService::REQUEST_CONFIG_ITEM_REQUIRED => true,
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'token_request_body',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                SrConfigService::REQUEST_CONFIG_ITEM_REQUIRED => true,
                SrConfigService::REQUEST_CONFIG_ITEM_NAME => 'token_request_query',
                SrConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                SrConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                SrConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
        ];
    }
}
