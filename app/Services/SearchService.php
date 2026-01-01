<?php

namespace App\Services;

use Truvoicer\TfDbReadCore\Services\ApiServices\ApiService;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrService;
use Truvoicer\TfDbReadCore\Services\BaseService;
use Truvoicer\TfDbReadCore\Services\Category\CategoryService;
use Truvoicer\TfDbReadCore\Services\Provider\ProviderService;

class SearchService extends BaseService
{
    private ProviderService $providerService;
    private SrService $requestService;
    private CategoryService $categoryService;
    private ApiService $apiService;

    public function __construct(
        ProviderService $providerService,
        SrService       $requestService,
        CategoryService $categoryService,
        ApiService      $apiService
    ) {
        parent::__construct();
        $this->providerService = $providerService;
        $this->requestService = $requestService;
        $this->categoryService = $categoryService;
        $this->apiService = $apiService;
    }

    public function performSearch($query)
    {
        $getProviders = $this->providerService->findByQuery($query);
        if (count($getProviders) > 0) {
            return [
                "type" => "provider",
                "items" => $getProviders
            ];
        }

        $getServiceRequests = $this->requestService->findByQuery($query);
        if (count($getServiceRequests) > 0) {
            return [
                "type" => "service_requests",
                "items" => $getServiceRequests
            ];
        }

        $getCategories = $this->categoryService->findByQuery($query);
        if (count($getCategories) > 0) {
            return [
                "type" => "categories",
                "items" => $getCategories
            ];
        }

        $getApiServices = $this->apiService->findByQuery($query);
        if (count($getApiServices) > 0) {
            return [
                "type" => "services",
                "items" => $getApiServices
            ];
        }
        return [];
    }


}
