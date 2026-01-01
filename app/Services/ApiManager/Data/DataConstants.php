<?php

namespace App\Services\ApiManager\Data;

use Truvoicer\TruFetcherGet\Enums\Api\ApiListKey;
use Truvoicer\TruFetcherGet\Enums\Property\PropertyType;

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

    public const REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT = 'big_text';
    public const REQUEST_CONFIG_VALUE_TYPE_TEXT = 'text';
    public const REQUEST_CONFIG_VALUE_TYPE_CHOICE = 'choice';
    public const REQUEST_CONFIG_VALUE_TYPE_LIST = 'list';
    public const REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST = 'entity_list';
    public const REQUEST_CONFIG_VALUE_TYPES = [
        self::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT,
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

    public const TOTAL_ITEMS = 'total_items';
    public const TOTAL_PAGES = 'total_pages';
    public const PAGE_SIZE = 'page_size';
    public const PAGE_NUMBER = 'page_number';
    public const OFFSET = 'offset';
    public const ITEM_ID = 'item_id';
    public const PREV_PAGE = 'prev_page';
    public const NEXT_PAGE = 'next_page';
    public const LAST_PAGE = 'last_page';
    public const HAS_MORE = 'has_more';
    public const PAGINATION_TYPE = 'pagination_type';

    public const SERVICE_RESPONSE_KEYS = [
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
    public const REQ_SR_FIELDS_FOR_XML_POPULATE = [
        ApiListKey::LIST_KEY->value => [
            'name' => ApiListKey::LIST_KEY->value,
        ],
        ApiListKey::LIST_ITEM_REPEATER_KEY->value => [
            'name' => ApiListKey::LIST_ITEM_REPEATER_KEY->value,
        ],
    ];
    public const REQ_SR_FIELDS_FOR_JSON_POPULATE = [
        ApiListKey::LIST_KEY->value => [
            'name' => ApiListKey::LIST_KEY->value,
        ],
    ];
    public const XML_SERVICE_RESPONSE_KEYS = [
    ];
    public const JSON_SERVICE_RESPONSE_KEYS = [
    ];

    public const CONTENT_TYPE_RESERVED_RESPONSE_KEYS = [
        self::XML_SERVICE_RESPONSE_KEYS,
        self::JSON_SERVICE_RESPONSE_KEYS,
    ];

    public const S_RESPONSE_KEY_GROUPS = [
        self::SERVICE_RESPONSE_KEYS,
        self::XML_SERVICE_RESPONSE_KEYS,
        self::JSON_SERVICE_RESPONSE_KEYS,
    ];
}
