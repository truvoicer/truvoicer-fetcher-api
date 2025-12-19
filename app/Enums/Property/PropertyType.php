<?php

namespace App\Enums\Property;

use App\Enums\Api\ApiMethod;
use App\Enums\Api\ApiResponseFormat;
use App\Enums\Api\ApiType;
use App\Enums\Entity\EntityType;
use App\Services\ApiManager\Data\DataConstants;

enum PropertyType: string
{
    case AI_TEMPERATURE = 'ai_temperature';
    case AI_SYSTEM_PROMPT = 'ai_system_prompt';
    case AI_PROMPT = 'ai_prompt';
    case PROVIDER = 'provider';
    case API_AUTH_TYPE = 'api_auth_type';
    case OAUTH_API_AUTH_TYPE = 'oauth_api_auth_type';
    case API_TYPE = 'api_type';
    case RESPONSE_FORMAT = 'response_format';
    case BASE_URL = 'base_url';
    case USER_ID = 'user_id';
    case ACCESS_TOKEN = 'access_token';
    case CLIENT_ID = 'client_id';
    case CLIENT_SECRET = 'client_secret';
    case OAUTH_TOKEN_URL = 'oauth_token_url';
    case LIST_ITEM_SEARCH_PRIORITY = 'list_item_search_priority';
    case ENDPOINT = 'endpoint';
    case METHOD = 'method';
    case HEADERS = 'headers';
    case BODY = 'body';
    case POST_BODY = 'post_body';
    case QUERY = 'query';
    case USERNAME = 'username';
    case PASSWORD = 'password';
    case BEARER_TOKEN = 'bearer_token';
    case SECRET_KEY = 'secret_key';
    case TOKEN_REQUEST_AUTH_TYPE = 'token_request_auth_type';
    case TOKEN_REQUEST_HEADERS = 'token_request_headers';
    case TOKEN_REQUEST_BODY = 'token_request_body';
    case TOKEN_REQUEST_POST_BODY = 'token_request_post_body';
    case TOKEN_REQUEST_QUERY = 'token_request_query';
    case TOKEN_REQUEST_METHOD = 'token_request_method';
    case TOKEN_REQUEST_USERNAME = 'token_request_username';
    case TOKEN_REQUEST_PASSWORD = 'token_request_password';
    case TOKEN_REQUEST_TOKEN = 'token_request_token';

    /**
     * Get configuration for this property type
     */
    public function config(): array
    {
        return match($this) {
            self::AI_TEMPERATURE => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'AI Temperature (E.g. 0.8)',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::AI_SYSTEM_PROMPT => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'AI System Prompt (E.g. You are a data fetcher etc.)',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::AI_PROMPT => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'AI Prompt',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_BIG_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::PROVIDER => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Provider',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_ENTITIES => [EntityType::ENTITY_PROVIDER->value],
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::API_AUTH_TYPE => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'API Authentication Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => [
                    DataConstants::OAUTH2,
                    DataConstants::AMAZON_SDK,
                    DataConstants::AUTH_BASIC,
                    DataConstants::AUTH_BEARER,
                    self::ACCESS_TOKEN->value,
                    DataConstants::AUTH_NONE,
                ],
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::OAUTH_API_AUTH_TYPE => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth API Authentication Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => [
                    DataConstants::AUTH_BASIC,
                    DataConstants::AUTH_BEARER,
                    self::ACCESS_TOKEN->value,
                    DataConstants::AUTH_NONE,
                ],
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::API_TYPE => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'API Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ApiType::values(),
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::RESPONSE_FORMAT => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Response Format',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ApiResponseFormat::values(),
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::BASE_URL => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Base URL',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::USER_ID => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'User ID',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::ACCESS_TOKEN => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Access Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::CLIENT_ID => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Client ID',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::CLIENT_SECRET => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Client Secret',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::OAUTH_TOKEN_URL => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token URL',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::LIST_ITEM_SEARCH_PRIORITY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'List Item Search Priority',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_ENTITIES => array_map(
                    fn(EntityType $entityType) => $entityType->value,
                    EntityType::cases()
                ),
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::ENDPOINT => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Endpoint',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::METHOD => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Request Method',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ApiMethod::values(),
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::HEADERS => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Headers',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::BODY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Body',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::POST_BODY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Post Body',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::QUERY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Query',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::USERNAME => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Username',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::PASSWORD => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Password',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::BEARER_TOKEN => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Bearer Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::SECRET_KEY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'Secret Key',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => false,
            ],
            self::TOKEN_REQUEST_AUTH_TYPE => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Auth Type',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['none', DataConstants::AUTH_BASIC, DataConstants::AUTH_BEARER, 'auth_token'],
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE => 'none',
            ],
            self::TOKEN_REQUEST_HEADERS => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Headers',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_BODY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Body',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_POST_BODY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Post Data',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_QUERY => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Query Data',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_LIST,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_METHOD => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Method',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE,
                DataConstants::REQUEST_CONFIG_ITEM_VALUE_CHOICES => ['get', 'post'],
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_USERNAME => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Username',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_PASSWORD => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Password',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
            self::TOKEN_REQUEST_TOKEN => [
                DataConstants::REQUEST_CONFIG_ITEM_NAME => $this->value,
                DataConstants::REQUEST_CONFIG_ITEM_LABEL => 'OAuth Token Request Access Token',
                DataConstants::REQUEST_CONFIG_ITEM_SELECTED_VALUE_TYPE => DataConstants::REQUEST_CONFIG_VALUE_TYPE_TEXT,
                DataConstants::REQUEST_CONFIG_ITEM_REQUIRED => true,
            ],
        };
    }

    /**
     * Get configuration for multiple property types
     */
    public static function configs(array $propertyTypes): array
    {
        return array_map(fn(self $type) => $type->config(), $propertyTypes);
    }

    /**
     * Get all provider properties configuration
     */
    public static function getProviderProperties(): array
    {
        $providerProperties = [
            self::AI_TEMPERATURE,
            self::AI_SYSTEM_PROMPT,
            self::AI_PROMPT,
            self::PROVIDER,
            self::API_AUTH_TYPE,
            self::OAUTH_API_AUTH_TYPE,
            self::API_TYPE,
            self::RESPONSE_FORMAT,
            self::BASE_URL,
            self::USER_ID,
            self::ACCESS_TOKEN,
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            self::OAUTH_TOKEN_URL,
            self::LIST_ITEM_SEARCH_PRIORITY,
        ];

        $serviceRequestConfig = [
            self::ENDPOINT,
            self::METHOD,
            self::HEADERS,
            self::BODY,
            self::POST_BODY,
            self::QUERY,
        ];

        $basicAuthConfig = [
            self::USERNAME,
            self::PASSWORD,
        ];

        $bearerAuthConfig = [
            self::BEARER_TOKEN,
        ];

        $oauthConfig = [
            self::TOKEN_REQUEST_AUTH_TYPE,
            self::TOKEN_REQUEST_HEADERS,
            self::TOKEN_REQUEST_BODY,
            self::TOKEN_REQUEST_POST_BODY,
            self::TOKEN_REQUEST_QUERY,
            self::TOKEN_REQUEST_METHOD,
            self::TOKEN_REQUEST_USERNAME,
            self::TOKEN_REQUEST_PASSWORD,
            self::TOKEN_REQUEST_TOKEN,
        ];

        return array_merge(
            self::configs($providerProperties),
            self::configs($serviceRequestConfig),
            self::configs($basicAuthConfig),
            self::configs($bearerAuthConfig),
            self::configs($oauthConfig)
        );
    }
}
