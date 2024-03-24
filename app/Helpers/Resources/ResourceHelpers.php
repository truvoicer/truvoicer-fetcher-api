<?php

namespace App\Helpers\Resources;

use App\Services\ApiManager\Response\Entity\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResourceHelpers
{
    const RESPONSE_PROPERTIES = [
        'status',
        'contentType',
        'provider',
        'requestCategory',
        'serviceRequest',
        'service',
    ];
    const INCLUDE_IN_COLLECTION_ITEMS = [
        'provider',
    ];

    public static function buildResponseProperties(array $data)
    {
        return array_filter($data, function ($value, $key) {
            return in_array($key, self::RESPONSE_PROPERTIES);
        }, ARRAY_FILTER_USE_BOTH);
    }
    public static function buildCollectionResults(Collection|LengthAwarePaginator $results)
    {
        $rc = new \ReflectionClass(ApiResponse::class);
        $responseVars = array_map(function ($var) {
            return $var->getName();
        }, $rc->getProperties(\ReflectionProperty::IS_PUBLIC));
        $results->transform(function ($result, $index) use ($responseVars) {
            return array_filter($result, function ($value, $key) use ($responseVars) {
                if (in_array($key, self::INCLUDE_IN_COLLECTION_ITEMS)) {
                    return true;
                }
                return !in_array($key, $responseVars) ;
            }, ARRAY_FILTER_USE_BOTH);
        });
        return $results;
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
