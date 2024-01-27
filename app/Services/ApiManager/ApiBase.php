<?php

namespace App\Services\ApiManager;

use App\Library\Defaults\DefaultData;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\BaseService;

class ApiBase
{
    const API_TYPE = "api_type";

    const OAUTH = 'oauth';
    const OAUTH_BEARER = 'oauth_bearer';
    const OAUTH_BASIC = 'oauth_basic';
    const OAUTH_BODY = 'oauth_body';
    const AMAZON_SDK = 'amazon_sdk';
    const ACCESS_TOKEN = 'access_token';
    const AUTH_BASIC = 'auth_basic';
    const AUTH_BEARER = 'auth_bearer';
    const AUTH_NONE = 'none';

    const API_AUTH_TYPE = "api_authentication_type";
    const OAUTH_TOKEN_URL_KEY = "oauth_token_url";
    const OAUTH_GRANT_TYPE_FIELD_NAME = "oauth_grant_type_field_name";
    const OAUTH_GRANT_TYPE_FIELD_VALUE = "oauth_grant_type_field_value";
    const OAUTH_SCOPE_FIELD_NAME = "oauth_scope_field_name";
    const OAUTH_SCOPE_FIELD_VALUE = "oauth_scope_field_value";
    const PARAM_FILTER_KEYS = [
        "API_BASE_URL" => [
            'placeholder' => "[API_BASE_URL]",
            'keymap' => null
        ],
        "PROVIDER_USER_ID" => [
            'placeholder' => "[PROVIDER_USER_ID]",
            'keymap' => null
        ],
        "SECRET_KEY" => [
            'placeholder' => "[SECRET_KEY]",
            'keymap' => null
        ],
        "ACCESS_KEY" => [
            'placeholder' => "[ACCESS_KEY]",
            'keymap' => null
        ],
        "CATEGORY" => [
            'placeholder' => "[CATEGORY]",
            'keymap' => null
        ],
        "TIMESTAMP" => [
            'placeholder' => "[TIMESTAMP]",
            'keymap' => null
        ],
        "QUERY" => [
            'placeholder' => "[QUERY]",
            'keymap' => null
        ],
        "LIMIT" => [
            'placeholder' => "[LIMIT]",
            'keymap' => DefaultData::REQUEST_KEYS['POST_PER_PAGE']
        ],
        "TOTAL_ITEMS" => [
            'placeholder' => '[TOTAL_ITEMS]',
            'keymap' => DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][SResponseKeysService::RESPONSE_KEY_NAME]
        ],
        "TOTAL_PAGES" => [
            'placeholder' => '[TOTAL_PAGES]',
            'keymap' => DefaultData::SERVICE_RESPONSE_KEYS['TOTAL_PAGES'][SResponseKeysService::RESPONSE_KEY_NAME]
        ],
        "CURRENT_PAGE" => [
            'placeholder' => '[CURRENT_PAGE]',
            'keymap' => DefaultData::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][SResponseKeysService::RESPONSE_KEY_NAME]
        ],
        "OFFSET" => [
            'placeholder' => '[OFFSET]',
            'keymap' => DefaultData::SERVICE_RESPONSE_KEYS['OFFSET'][SResponseKeysService::RESPONSE_KEY_NAME]
        ],
    ];

    const PAGINATION_TYPES = [
      [
          'name' => 'offset',
          'label' => 'Offset'
      ],
      [
          'name' => 'page',
          'label' => 'Page'
      ],
    ];

}
