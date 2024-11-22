<?php

namespace App\Enums\Import;

enum ImportType: string
{
    case SR_SCHEDULE = "sr_schedule";
    case SR_RATE_LIMIT = "sr_rate_limit";
    case SR_RESPONSE_KEY = "sr_response_key";
    case SR_PARAMETER = "sr_parameter";
    case SR_CONFIG = "sr_config";
    case SR = "sr";
    case PROVIDER = "provider";
    case SERVICE = "service";
    case CATEGORY = "category";
    case PROPERTY = "property";
}
