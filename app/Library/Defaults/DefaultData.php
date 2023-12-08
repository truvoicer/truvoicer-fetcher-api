<?php

namespace App\Library\Defaults;

use App\Services\ApiManager\ApiBase;
use App\Services\ApiServices\ServiceRequests\RequestConfigService;

class DefaultData
{

    public const REQUEST_KEYS = [
        "POST_PER_PAGE" => "posts_per_page",
    ];
    public const SERVICE_RESPONSE_KEYS = [
        "ITEMS_ARRAY" => "items_array",
        "ITEM_ID" => "item_id",
        "TOTAL_ITEMS" => "total_items",
        "TOTAL_PAGES" => "total_pages",
        "PAGE_SIZE" => "page_size",
        "PAGE_COUNT" => "page_count",
        "PAGE_NUMBER" => "page_number",
        "OFFSET" => "offset",
    ];
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

    public static function getServiceResponseKeys()
    {
        return self::SERVICE_RESPONSE_KEYS;
    }

    public static function getServiceRequestConfig()
    {
        return [
            [
                RequestConfigService::REQUEST_CONFIG_ITEM_NAME => 'endpoint',
                RequestConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                RequestConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                RequestConfigService::REQUEST_CONFIG_ITEM_NAME => 'request_method',
                RequestConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "choice",
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                RequestConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
            ],
            [
                RequestConfigService::REQUEST_CONFIG_ITEM_NAME => 'headers',
                RequestConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "list",
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                RequestConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
        ];
    }
    public static function getServiceRequestBasicAuthConfig()
    {
        return [
            [
                RequestConfigService::REQUEST_CONFIG_ITEM_NAME => 'username',
                RequestConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                RequestConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ],
            [
                RequestConfigService::REQUEST_CONFIG_ITEM_NAME => 'password',
                RequestConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                RequestConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
    public static function getServiceRequestBearerAuthConfig()
    {
        return [
            [
                RequestConfigService::REQUEST_CONFIG_ITEM_NAME => 'bearer_token',
                RequestConfigService::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => "text",
                RequestConfigService::REQUEST_CONFIG_ITEM_VALUE => '',
                RequestConfigService::REQUEST_CONFIG_ITEM_ARRAY_VALUE => [],
            ]
        ];
    }
}
