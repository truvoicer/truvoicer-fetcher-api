<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiSearchItemResource;
use App\Http\Resources\ApiMongoDBSearchListResourceCollection;
use App\Models\S;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
    )
    {
        parent::__construct(
            $providers,
            $providerService,
            $categoryService,
            $apiService,
            $apiResponse,
            $this->apiRequestService
        );

    }

    public function searchInit(string $type, array $providers): void
    {
        $this->prepareProviders($providers, $type);

        $this->apiRequestSearchService->setProviders($this->providers);
        $this->apiRequestSearchService->setNotFoundProviders($this->notFoundProviders);
        $this->apiRequestSearchService->setItemSearchData($this->itemSearchData);
        $this->apiRequestSearchService->setService($this->service);
        $this->apiRequestSearchService->setType($type);

    }

    public function runListSearch(string $type, array $providers, ?array $query = []): Collection|LengthAwarePaginator
    {
        $this->searchInit($type, $providers);
        return $this->apiRequestSearchService->runListSearch($query);
    }

    public function runItemSearch(string $type, array $providers): array|null
    {
        $this->searchInit($type, $providers);
        return $this->apiRequestSearchService->runSingleItemSearch('detail');
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

    public function compareResultsWithData(Collection|LengthAwarePaginator $results) {
        $compare = array_map(function($searchItem) use ($results) {
            $filterByProvider = $results->where('provider', $searchItem['provider_name']);
            $itemIds = array_map('intval', $searchItem['ids']);
            $searchItem['ids'] = array_filter($itemIds, function($id) use ($filterByProvider,$itemIds) {
                return !$filterByProvider->where(function ($item) use ($id, $itemIds) {
                    if (empty($item['item_id'])) {
                        return false;
                    }
                    if (is_array($item['item_id'])) {
                        $filterFind = array_filter($item['item_id'], function($id) use ($itemIds) {
                            return in_array((int)$id['data'], $itemIds);
                        });
                        return !count($filterFind);
                    }
                    return !in_array($item['item_id'], $itemIds);
                })->count();
            });
            $searchItem['ids'] = $filterByProvider->filter(function($item) use ($itemIds) {
                if (empty($item['item_id'])) {
                    return false;
                }
                if (is_array($item['item_id'])) {
                    $filterFind = array_filter($item['item_id'], function($id) use ($itemIds) {
                        return in_array((int)$id['data'], $itemIds);
                    });
                    return !count($filterFind);
                }
                return !in_array($item['item_id'], $itemIds);
            });
            return $searchItem;
        }, $this->itemSearchData);

        dd($compare);
    }
    public function searchOperation(string $type, array $providers, string $serviceName, ?array $data = [])
    {
        if (!count($providers)) {
            return false;
        }
        $getService = $this->findService($serviceName);
        if (!$getService instanceof S) {
            return false;
        }
        $this->setService($getService);


        $providerData = $this->buildProviderData($providers);
        switch ($type) {
            case SrRepository::SR_TYPE_LIST:
                return $this->runListSearch(
                    $type,
                    $providerData,
                    $data
                );
            case SrRepository::SR_TYPE_MIXED:
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
            return $this->runItemSearch(
                    $type,
                    $providerData,
                    $data['item_id'] ?? null
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

}
