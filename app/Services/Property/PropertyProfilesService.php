<?php

namespace App\Services\Property;

use Truvoicer\TfDbReadCore\Enums\Property\PropertyType;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;

class PropertyProfilesService {
    public const PROFILES = [
        [
            'name' => 'rss_feed',
            'properties' => [
                PropertyType::API_AUTH_TYPE->value,
                PropertyType::API_TYPE->value,
                PropertyType::RESPONSE_FORMAT->value,
                PropertyType::BASE_URL->value,
                PropertyType::METHOD->value
            ]
        ]
    ];
}
