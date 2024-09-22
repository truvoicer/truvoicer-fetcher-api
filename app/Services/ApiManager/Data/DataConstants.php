<?php

namespace App\Services\ApiManager\Data;


class DataConstants
{

    const RESPONSE_KEY_REQUIRED = "required";
    const RESPONSE_KEY_NAME = "name";

    const REQUEST_CONFIG_ITEM_REQUIRED = "required";
    const REQUEST_CONFIG_ITEM_NAME = "name";
    const REQUEST_CONFIG_ITEM_LABEL = "label";
    const REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE = "value_type";
    const REQUEST_CONFIG_ITEM_VALUE = "value";
    const REQUEST_CONFIG_ITEM_ARRAY_VALUE = "array_value";
    const REQUEST_CONFIG_ITEM_VALUE_CHOICES = "value_choices";
    const REQUEST_CONFIG_ITEM_VALUE_ENTITIES = "entities";

    public const REQUEST_CONFIG_VALUE_TYPE_TEXT = 'text';
    public const REQUEST_CONFIG_VALUE_TYPE_CHOICE = 'choice';
    public const REQUEST_CONFIG_VALUE_TYPE_LIST = 'list';
    public const REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST = 'entity_list';
    public const REQUEST_CONFIG_VALUE_TYPES = [
        self::REQUEST_CONFIG_VALUE_TYPE_TEXT,
        self::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
        self::REQUEST_CONFIG_VALUE_TYPE_LIST,
        self::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST,
    ];

    const API_TYPE = "api_type";

    const OAUTH2 = 'oauth2';
    const AMAZON_SDK = 'amazon_sdk';
    const ACCESS_TOKEN = 'access_token';
    const AUTH_BASIC = 'auth_basic';
    const AUTH_BEARER = 'auth_bearer';
    const AUTH_NONE = 'none';

    const LIST_ITEM_SEARCH_PRIORITY = "list_item_search_priority";
    const API_AUTH_TYPE = "api_authentication_type";
    const OAUTH_API_AUTH_TYPE = "oauth_api_authentication_type";
    const OAUTH_TOKEN_URL = "oauth_token_url";
    const CLIENT_ID = "client_id";
    const CLIENT_SECRET = "client_secret";
    const SECRET_KEY = "secret_key";
    const USER_ID = "user_id";
    const BASE_URL = "base_url";

    public const TOKEN_REQUEST_AUTH_TYPE = 'token_request_auth_type';
    public const TOKEN_REQUEST_HEADERS = 'token_request_headers';
    public const TOKEN_REQUEST_POST_BODY = 'token_request_post_body';
    public const TOKEN_REQUEST_BODY = 'token_request_body';
    public const TOKEN_REQUEST_QUERY = 'token_request_query';
    public const TOKEN_REQUEST_METHOD = 'token_request_method';
    public const TOKEN_REQUEST_USERNAME = 'token_request_username';
    public const TOKEN_REQUEST_PASSWORD = 'token_request_password';
    public const TOKEN_REQUEST_TOKEN = 'token_request_token';

    public const BEARER_TOKEN = 'bearer_token';
    public const HEADERS = 'headers';
    public const BODY = 'body';
    public const POST_BODY = 'post_body';
    public const QUERY = 'query';
    public const METHOD = 'method';
    public const USERNAME = 'username';
    public const PASSWORD = 'password';
    public const TOKEN = 'token';
    public const ENDPOINT = 'endpoint';
    public const RESPONSE_FORMAT = 'response_format';

    const PARAM_FILTER_KEYS = [
        "API_BASE_URL" => [
            'placeholder' => "[API_BASE_URL]",
            'keymap' => null
        ],
        "BASE_URL" => [
            'placeholder' => "[BASE_URL]",
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
        "OAUTH_GRANT_TYPE_FIELD_NAME" => [
            'placeholder' => "[OAUTH_GRANT_TYPE_FIELD_NAME]",
            'keymap' => null
        ],
        "OAUTH_GRANT_TYPE_FIELD_VALUE" => [
            'placeholder' => "[OAUTH_GRANT_TYPE_FIELD_VALUE]",
            'keymap' => null
        ],
        "OAUTH_SCOPE_FIELD_NAME" => [
            'placeholder' => "[OAUTH_SCOPE_FIELD_NAME]",
            'keymap' => null
        ],
        "OAUTH_SCOPE_FIELD_VALUE" => [
            'placeholder' => "[OAUTH_SCOPE_FIELD_VALUE]",
            'keymap' => null
        ],
        "CLIENT_ID" => [
            'placeholder' => "[CLIENT_ID]",
            'keymap' => null
        ],
        "CLIENT_SECRET" => [
            'placeholder' => "[CLIENT_SECRET]",
            'keymap' => null
        ],
        "ACCESS_KEY" => [
            'placeholder' => "[ACCESS_KEY]",
            'keymap' => null
        ],
        "ACCESS_TOKEN" => [
            'placeholder' => "[ACCESS_TOKEN]",
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
            'placeholder' => "[PAGE_SIZE]",
            'keymap' => self::SERVICE_RESPONSE_KEYS['PAGE_SIZE'][self::RESPONSE_KEY_NAME]
        ],
        "TOTAL_ITEMS" => [
            'placeholder' => '[TOTAL_ITEMS]',
            'keymap' => self::SERVICE_RESPONSE_KEYS['TOTAL_ITEMS'][self::RESPONSE_KEY_NAME]
        ],
        "TOTAL_PAGES" => [
            'placeholder' => '[TOTAL_PAGES]',
            'keymap' => self::SERVICE_RESPONSE_KEYS['TOTAL_PAGES'][self::RESPONSE_KEY_NAME]
        ],
        "CURRENT_PAGE" => [
            'placeholder' => '[CURRENT_PAGE]',
            'keymap' => self::SERVICE_RESPONSE_KEYS['PAGE_NUMBER'][self::RESPONSE_KEY_NAME]
        ],
        "OFFSET" => [
            'placeholder' => '[OFFSET]',
            'keymap' => self::SERVICE_RESPONSE_KEYS['OFFSET'][self::RESPONSE_KEY_NAME]
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
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::ITEMS_ARRAY,
        ],
        "TOTAL_ITEMS" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::TOTAL_ITEMS,
        ],
        "TOTAL_PAGES" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::TOTAL_PAGES,
        ],
        "PAGE_SIZE" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::PAGE_SIZE,
        ],
        "PAGE_NUMBER" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::PAGE_NUMBER,
        ],
        "OFFSET" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::OFFSET,
        ],
        "ITEM_ID" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => self::ITEM_ID,
        ],
    ];
    public const XML_SERVICE_RESPONSE_KEYS = [
        "ITEM_REPEATER_KEY" => [
            self::RESPONSE_KEY_REQUIRED => true,
            self::RESPONSE_KEY_NAME => "item_repeater_key"
        ],
    ];

    public const S_RESPONSE_KEY_GROUPS = [
        self::SERVICE_RESPONSE_KEYS,
        self::XML_SERVICE_RESPONSE_KEYS
    ];
}
