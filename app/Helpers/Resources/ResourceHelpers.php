<?php

namespace App\Helpers\Resources;

use App\Services\ApiManager\Response\Entity\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResourceHelpers
{
    const RESPONSE_PROPERTIES = [
        'status',
        'contentType',
    ];
    const INCLUDE_IN_COLLECTION_ITEMS = [
        'provider',
        'requestCategory',
        'serviceRequest',
        'service',
    ];

    public static function buildResponseProperties(array $data)
    {
        return array_filter($data, function ($value, $key) {
            return in_array($key, self::RESPONSE_PROPERTIES);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public static function processDates(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $data[$key] = Carbon::createFromImmutable($value->toDateTimeImmutable());
            }
        }
        return $data;
    }

    public static function buildCollectionResults(array $result)
    {
        $rc = new \ReflectionClass(ApiResponse::class);
        $responseVars = array_map(function ($var) {
            return $var->getName();
        }, $rc->getProperties(\ReflectionProperty::IS_PUBLIC));
        
        $result = array_filter($result, function ($value, $key) use ($responseVars) {
            if (in_array($key, self::INCLUDE_IN_COLLECTION_ITEMS)) {
                return true;
            }
            return !in_array($key, $responseVars) ;
        }, ARRAY_FILTER_USE_BOTH);
        return self::processDates($result);
    
    }
    public static function buildCollectionResponseProperties(Collection|LengthAwarePaginator $data)
    {
        $filterResponseProps = [];
        foreach ($data as $item) {
            $filter = self::buildResponseProperties($item);

            if (count($filter) === count(self::RESPONSE_PROPERTIES)) {
                $filterResponseProps = $filter;
                break;
            }
        }
        return $filterResponseProps;
    }
}
