<?php

namespace App\Services\ApiManager\Data;

use Truvoicer\TruFetcherGet\Enums\Property\PropertyType;

class DefaultData
{
    public const TEST_USER_DATA = [
        'name' => 'Test User',
        'email' => 'test@user.com',
        'password' => 'password',
    ];

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
        return PropertyType::getProviderProperties();
    }

    public static function getServiceRequestConfig()
    {
        return PropertyType::configs([
            PropertyType::ENDPOINT,
            PropertyType::METHOD,
            PropertyType::HEADERS,
            PropertyType::BODY,
            PropertyType::POST_BODY,
            PropertyType::QUERY,
        ]);
    }

    public static function getServiceRequestBasicAuthConfig()
    {
        return PropertyType::configs([
            PropertyType::USERNAME,
            PropertyType::PASSWORD,
        ]);
    }

    public static function getServiceRequestBearerAuthConfig()
    {
        return PropertyType::configs([
            PropertyType::BEARER_TOKEN,
        ]);
    }

    public static function getServiceRequestOauthConfig()
    {
        return PropertyType::configs([
            PropertyType::TOKEN_REQUEST_AUTH_TYPE,
            PropertyType::TOKEN_REQUEST_HEADERS,
            PropertyType::TOKEN_REQUEST_BODY,
            PropertyType::TOKEN_REQUEST_POST_BODY,
            PropertyType::TOKEN_REQUEST_QUERY,
            PropertyType::TOKEN_REQUEST_METHOD,
            PropertyType::TOKEN_REQUEST_USERNAME,
            PropertyType::TOKEN_REQUEST_PASSWORD,
            PropertyType::TOKEN_REQUEST_TOKEN,
        ]);
    }
}
