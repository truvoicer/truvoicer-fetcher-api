<?php

namespace App\Enums\Property;

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
}
