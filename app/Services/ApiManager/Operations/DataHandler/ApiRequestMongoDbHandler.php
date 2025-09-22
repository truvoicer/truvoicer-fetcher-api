<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiMongoDbSearchListCollection;
use App\Http\Resources\ApiSearchItemResource;
use App\Models\S;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\Category\CategoryService;
use App\Services\EntityService;
use App\Services\Provider\ProviderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use MongoDB\Model\BSONDocument;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestMongoDbHandler extends ApiRequestDataHandler
{
    public function __construct(
        protected EloquentCollection               $providers,
        protected ProviderService                  $providerService,
        protected readonly ApiRequestSearchService $apiRequestSearchService,
        protected ApiService                       $apiService,
        protected S                                $service,
        protected CategoryService                  $categoryService,
        protected ApiResponse                      $apiResponse,
        protected ApiRequestService                $apiRequestService,
    ) {
        parent::__construct(
            $providers,
            $providerService,
            $categoryService,
            $apiService,
            $apiResponse,
            $this->apiRequestService
        );
    }

    public function searchInit(string $type): void
    {
        $this->apiRequestSearchService->setProviders($this->providers);
        $this->apiRequestSearchService->setNotFoundProviders($this->notFoundProviders);
        $this->apiRequestSearchService->setItemSearchData($this->itemSearchData);
        $this->apiRequestSearchService->setService($this->service);
        $this->apiRequestSearchService->setType($type);
    }

    public function runListSearch(string $type, array $providers, ?array $query = []): Collection|LengthAwarePaginator
    {
        $this->prepareProviders($providers, $type);
        $this->searchInit($type);
        return $this->apiRequestSearchService->runListSearch($query);
    }

    public function runItemSearch(string $type, array $providers): BSONDocument|null
    {
        $this->prepareProviders($providers, $type);
        $this->searchInit($type);
        return $this->apiRequestSearchService->runSingleItemSearch($type);
    }

    private function isSingleProvider(array $data): bool
    {
        if (!count($data)) {
            return false;
        }
        return (!is_array($data[array_key_first($data)]));
    }

    private function buildProviderData(array $data): array
    {
        if ($this->isSingleProvider($data)) {
            return [$data];
        }
        return $data;
    }
    public function findItemId(array $item): int|false
    {
        if (empty($item['item_id'])) {
            return false;
        }
        if (is_array($item['item_id'])) {
            $filterFind = array_column($item['item_id'], 'data');
            if (!count($filterFind)) {
                return false;
            }
            return $filterFind[0];
        }
        return (int)$item['item_id'];
    }
    public function compareResultsWithData(Collection|LengthAwarePaginator $results): array
    {
        $mapMissingIds = array_map(function ($searchItem) use ($results) {
            $filterByProvider = $results->where('provider', $searchItem['provider_name']);
            $itemIds = array_map(function ($item) {
                return $this->findItemId($item);
            }, $filterByProvider->toArray());

            $itemIds = array_filter($itemIds, function ($id) {
                return $id !== false;
            });
            if (!empty($searchItem['ids']) && is_array($searchItem['ids'])) {
                $searchItem['ids'] =  array_map('intval', $searchItem['ids']);
                $searchItem['ids'] = array_values(
                    array_filter($searchItem['ids'], function ($id) use ($itemIds) {
                        return !in_array($id, $itemIds);
                    })
                );
            }
            return $searchItem;
        }, $this->itemSearchData);
        return array_filter($mapMissingIds, function ($item) {
            return !empty($item['ids']);
        });
    }

    public function searchOperation(string $type, array $providers, string $serviceName, ?array $data = [])
    {
        // if (!count($providers)) {
        //     return false;
        // }

        $getService = $this->findService($serviceName);
        if (!$getService instanceof S) {
            return false;
        }
        $this->setService($getService);

        $providerData = $this->buildProviderData($providers);

        switch ($type) {
            case SrRepository::SR_TYPE_LIST:
            case SrRepository::SR_TYPE_MIXED:
                // dd(
                //     $this->runListSearch(
                //         $type,
                //         $providerData,
                //         $data
                //     )
                // );
                return new ApiMongoDbSearchListCollection(
                    $this->runListSearch(
                        $type,
                        $providerData,
                        $data
                    )
                );
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
                return new ApiSearchItemResource(
                    $this->runItemSearch(
                        $type,
                        $providerData
                    )
                );
            default:
                return false;
        }
    }


    private function findService(string $serviceName): S|false
    {
        $sr = $this->apiService->getServiceRepository()->findByName($serviceName);
        if (!$sr instanceof S) {
            return false;
        }
        return $sr;
    }

    public function setService(S $service): void
    {
        $this->service = $service;
    }

    public function getApiRequestSearchService(): ApiRequestSearchService
    {
        return $this->apiRequestSearchService;
    }
}
