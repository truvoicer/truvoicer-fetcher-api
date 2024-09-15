<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiMongoDBSearchListResourceCollection;
use App\Http\Resources\ApiSearchItemResource;
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
        private ApiRequestMongoDbHandler $apiRequestMongoDbHandler,
        private ApiRequestApiDirectHandler $apiRequestApiDirectHandler,
        private ProviderService $providerService,
        private SrService $srService
    )
    {
    }

    public function searchOperation(
        string $fetchType,
        string $serviceType,
        array $providers,
        string $serviceName,
        ?array $data = []
    )
    {
        if (!count($providers)) {
            return false;
        }

        $this->apiRequestMongoDbHandler->setUser($this->user);
        $this->apiRequestApiDirectHandler->setUser($this->user);

        switch ($fetchType) {
            case 'mixed':
                $response = $this->apiRequestMongoDbHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $data
                );
                $compare = $this->apiRequestMongoDbHandler->compareResultsWithData(
                    $response
                );
                if (!count($compare)) {
                    break;
                }
                $priority = $this->prioritySearchHandler(
                    $compare,
                    $response,
                    $data
                );
                dd($priority);
                break;
            case 'database':
                $response = $this->apiRequestMongoDbHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $data
                );
                break;
            case 'api_direct':
                $response = $this->apiRequestApiDirectHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $data
                );
                break;
            default:
                return false;
        }
        if (!$response) {
            return false;
        }
        switch ($serviceType) {
            case SrRepository::SR_TYPE_LIST:
                return new ApiMongoDBSearchListResourceCollection($response);
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
                return new ApiSearchItemResource($response);
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

            $srSearchPriorityData = $this->providerService->getProviderPropertyValue(
                $provider,
                DataConstants::LIST_ITEM_SEARCH_PRIORITY
            );
            foreach ($srSearchPriorityData as $srSearchPriorityDatum) {
                if (empty($srSearchPriorityDatum[EntityService::ENTITY_SR]) || !is_array($srSearchPriorityDatum[EntityService::ENTITY_SR]) || !count($srSearchPriorityDatum[EntityService::ENTITY_SR])) {
                    continue;
                }
                foreach ($srSearchPriorityDatum[EntityService::ENTITY_SR] as $srSearchPriority) {
                    if (empty($srSearchPriority['type'])) {
                        continue;
                    }

                    switch ($srSearchPriority['type']) {
                        case SrRepository::SR_TYPE_LIST:

                            $sr = $this->srService->getServiceRequestRepository()->findById($srSearchPriority['id']);
                            $response = $this->apiRequestApiDirectHandler->searchOperationBySr(
                                $sr,
                                $data
                            );
                            break;
                        default:

                            $response = $this->apiRequestMongoDbHandler->runListSearch(
                                $srSearchPriority['type'],
                                $srSearchPriority['providers'],
                                $srSearchPriority['service_name']
                            );
                            $response = $this->apiRequestApiDirectHandler->searchOperation(
                                $serviceType, $providers, $serviceName, $data
                            );
                            break;
                    }
                }
                dd($srSearchPriorityData);
            }
        }
        dd($this->itemSearchData);
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
