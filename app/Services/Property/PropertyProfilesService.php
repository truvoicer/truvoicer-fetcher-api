<?php

namespace App\Services\Property;

use App\Services\ApiManager\Data\DataConstants;

class PropertyProfilesService {
    public const PROFILES = [
        [
            'name' => 'rss_feed',
            'properties' => [
                DataConstants::API_AUTH_TYPE,
                DataConstants::API_TYPE,
                DataConstants::RESPONSE_FORMAT,
                DataConstants::BASE_URL,
                DataConstants::METHOD
            ]
        ]
    ];
}