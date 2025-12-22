<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Enums\Entity\EntityType;
use App\Enums\Property\PropertyType;
use App\Enums\Sr\SrType;
use App\Http\Resources\ApiMongoDbSearchListCollection;
use App\Http\Resources\ApiDirectSearchListCollection;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\EntityService;
use App\Services\Provider\ProviderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use stdClass;

class ApiRequestDataInterface
{
    private User $user;
    private array $requestData = [];

    public function __construct(
        private ApiRequestMongoDbHandler   $apiRequestMongoDbHandler,
        private ApiRequestApiDirectHandler $apiRequestApiDirectHandler,
        private ProviderService            $providerService,
        private SrService                  $srService
    ) {}


    public function searchOperation(
        string $fetchType,
        string $serviceType,
        array  $providers,
        string $serviceName,
    ) {
        // if (!count($providers)) {
        //     return false;
        // }
        $apiFetchOnRecordNotFound = (
            !empty($this->requestData['api_fetch_on_record_not_found']))
            ? $this->requestData['api_fetch_on_record_not_found']
            : false;
        $filteredRequestData = array_filter($this->requestData, function ($value) {
            return !in_array($value, ApiRequestDataHandler::RESERVED_REQUEST_KEYS);
        }, ARRAY_FILTER_USE_KEY);

        $this->apiRequestMongoDbHandler->setUser($this->user)
        ->setRequestData($this->requestData);
        $this->apiRequestApiDirectHandler->setUser($this->user)
        ->setRequestData($this->requestData);

        switch ($fetchType) {
            case 'database':
                $response = $this->apiRequestMongoDbHandler->searchOperation(
                    $serviceType,
                    $providers,
                    $serviceName,
                    $filteredRequestData
                );

                if (!$response?->resource && $apiFetchOnRecordNotFound) {

                    return $this->apiRequestApiDirectHandler->searchOperation(
                        $serviceType,
                        $providers,
                        $serviceName,
                    );
                }
                return $response;
                break;
            case 'api_direct':
                return $this->apiRequestApiDirectHandler->searchOperation(
                    $serviceType,
                    $providers,
                    $serviceName,
                );
                break;
            default:
                return false;
        }
    }

    private function prioritySearchHandler(array $searchData, Collection|LengthAwarePaginator $results): Collection|LengthAwarePaginator
    {
        $this->apiRequestMongoDbHandler->setItemSearchData($searchData);
        foreach ($searchData as $searchItem) {
            if (empty($searchItem['ids']) || !is_array($searchItem['ids'])) {
                continue;
            }
            if (!count($searchItem['ids'])) {
                continue;
            }
            $provider = $this->providerService->getUserProviderByName($this->user, $searchItem['provider_name']);

            if (!$provider) {
                continue;
            }
            $response = $this->getProviderSomethingByItemIds($provider, $searchItem['ids']);
            foreach ($response as $item) {
                $results->add($item);
            }
        }
        return $results;
    }

    private function getProviderSomethingByItemIds(Provider $provider, array $ids): Collection
    {
        $collection = new Collection();
        $srs = $this->buildSrsPriorityArray($provider);
        foreach ($ids as $id) {
            $response = $this->getProviderSomethingByItemId($srs, $id);
            if ($response) {
                $collection->add($response);
            }
        }

        return $collection;
    }

    private function getProviderSomethingByItemId(array $srs, string|int $id): array|null|\MongoDB\Model\BSONDocument|stdClass
    {
        foreach ($srs as $sr) {
            switch ($sr->type) {
                case SrType::LIST:
                    $response = $this->apiSearchBySr($sr, $id);
                    if (!$response) {
                        break;
                    }
                    return $response->first();
                case SrType::DETAIL:
                case SrType::SINGLE:
                    $response = $this->apiRequestMongoDbHandler->runItemSearch(
                        $sr->type,
                        [
                            [
                                'provider_name' => $sr->provider->name,
                                'item_id' => $id
                            ]
                        ],
                    );
                    if ($response) {
                        return $response;
                    }
                    $response = $this->apiSearchBySr($sr, $id);

                    if ($response) {
                        return $response;
                    }
                    break;
            }
        }
        return null;
    }

    private function apiSearchBySr(Sr $sr, string|int $id): Collection|array|null
    {
        $response = $this->apiRequestApiDirectHandler->searchOperationBySr(
            $sr,
            ['item_id' => $id]
        );
        if (!$response) {
            return null;
        }
        switch ($sr->type) {
            case SrType::LIST:
                if ($response->isEmpty()) {
                    return null;
                }
                $item = $response->first();
                $itemId = $this->apiRequestMongoDbHandler->findItemId($item);
                if (!$itemId) {
                    return null;
                }
                if ($itemId !== (int)$id) {
                    return null;
                }
                return $response->first();
            case SrType::DETAIL:
            case SrType::SINGLE:
                $itemId = $this->apiRequestMongoDbHandler->findItemId($response);
                if (!$itemId) {
                    return null;
                }
                if ($itemId !== (int)$id) {
                    return null;
                }
                return $response;
            default:
                return null;
        }
    }

    private function buildSrsPriorityArray(Provider $provider)
    {

        $srSearchPriorityData = $this->providerService->getProviderPropertyValue(
            $provider,
            PropertyType::LIST_ITEM_SEARCH_PRIORITY->value
        );
        $filtered = array_filter($srSearchPriorityData, function ($srSearchPriorityDatum) {
            if (
                empty($srSearchPriorityDatum[EntityType::ENTITY_SR->value]) ||
                !is_array($srSearchPriorityDatum[EntityType::ENTITY_SR->value]) ||
                !count($srSearchPriorityDatum[EntityType::ENTITY_SR->value])
            ) {
                return false;
            }
            $filtered = array_filter($srSearchPriorityDatum[EntityType::ENTITY_SR->value], function ($srSearchPriority) {
                return !empty($srSearchPriority['type']) && !empty($srSearchPriority['id']);
            });
            return count($filtered);
        }, ARRAY_FILTER_USE_BOTH);
        return array_map(function ($srSearchPriorityDatum) {
            $filtered = array_filter($srSearchPriorityDatum[EntityType::ENTITY_SR->value], function ($srSearchPriority) {
                return !empty($srSearchPriority['type']) && !empty($srSearchPriority['id']);
            });
            $firstSr = $this->srService->getServiceRequestRepository()->findById($filtered[array_key_first($filtered)]['id']);
            if (!$firstSr) {
                return false;
            }
            return $firstSr;
        }, $filtered);
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }
    public function setRequestData(array $data): self
    {
        $this->requestData = $data;
        return $this;
    }
}
