<?php

namespace App\Services\ApiManager\Operations\DataHandler;

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

class ApiRequestDataInterface
{
    private User $user;

    public function __construct(
        private ApiRequestMongoDbHandler   $apiRequestMongoDbHandler,
        private ApiRequestApiDirectHandler $apiRequestApiDirectHandler,
        private ProviderService            $providerService,
        private SrService                  $srService
    )
    {
    }

    public function searchOperation(
        string $fetchType,
        string $serviceType,
        array  $providers,
        string $serviceName,
        ?array $data = []
    )
    {
        // if (!count($providers)) {
        //     return false;
        // }
        $filteredRequestData = array_filter($data, function ($value) {
            return !in_array($value, ApiRequestDataHandler::RESERVED_REQUEST_KEYS);
        }, ARRAY_FILTER_USE_KEY);
        $this->apiRequestMongoDbHandler->setUser($this->user);
        $this->apiRequestApiDirectHandler->setUser($this->user);

        switch ($fetchType) {
            case 'mixed':
                $response = $this->apiRequestMongoDbHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $filteredRequestData
                );
                $compare = $this->apiRequestMongoDbHandler->compareResultsWithData(
                    $response
                );

                if (!count($compare)) {
                    break;
                }
                return $this->prioritySearchHandler(
                    $compare,
                    $response,
                    $data
                );
                break;
            case 'database':
                return $this->apiRequestMongoDbHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $filteredRequestData
                );
                break;
            case 'api_direct':
                return $this->apiRequestApiDirectHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $data
                );
                break;
            default:
                return false;
        }
    }

    private function prioritySearchHandler(array $searchData, Collection|LengthAwarePaginator $results, ?array $data = []): Collection|LengthAwarePaginator
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

    private function getProviderSomethingByItemId(array $srs, string|int $id): array|null
    {
        foreach ($srs as $sr) {
            switch ($sr->type) {
                case SrRepository::SR_TYPE_LIST:
                    $response = $this->apiSearchBySr($sr, $id);
                    if (!$response) {
                        break;
                    }
                    return $response->first();
                case SrRepository::SR_TYPE_DETAIL:
                case SrRepository::SR_TYPE_SINGLE:
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

    private function apiSearchBySr(Sr $sr, string|int $id): Collection|array|null {
        $response = $this->apiRequestApiDirectHandler->searchOperationBySr(
            $sr,
            ['item_id' => $id]
        );
        if (!$response) {
            return null;
        }
        switch ($sr->type) {
            case SrRepository::SR_TYPE_LIST:
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
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
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
            DataConstants::LIST_ITEM_SEARCH_PRIORITY
        );
        $filtered = array_filter($srSearchPriorityData, function ($srSearchPriorityDatum) {
            if (
                empty($srSearchPriorityDatum[EntityService::ENTITY_SR]) ||
                !is_array($srSearchPriorityDatum[EntityService::ENTITY_SR]) ||
                !count($srSearchPriorityDatum[EntityService::ENTITY_SR])
            ) {
                return false;
            }
            $filtered = array_filter($srSearchPriorityDatum[EntityService::ENTITY_SR], function ($srSearchPriority) {
                return !empty($srSearchPriority['type']) && !empty($srSearchPriority['id']);
            });
            return count($filtered);
        }, ARRAY_FILTER_USE_BOTH);
        return array_map(function ($srSearchPriorityDatum) {
            $filtered = array_filter($srSearchPriorityDatum[EntityService::ENTITY_SR], function ($srSearchPriority) {
                return !empty($srSearchPriority['type']) && !empty($srSearchPriority['id']);
            });
            $firstSr = $this->srService->getServiceRequestRepository()->findById($filtered[array_key_first($filtered)]['id']);
            if (!$firstSr) {
                return false;
            }
            return $firstSr;
        }, $filtered);
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
