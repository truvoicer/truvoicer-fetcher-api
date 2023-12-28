<?php

namespace App\Services;

use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;

class SearchService extends BaseService
{
    private ProviderService $providerService;
    private RequestService $requestService;
    private CategoryService $categoryService;
    private ApiService $apiService;

    public function __construct(
        ProviderService $providerService,
        RequestService $requestService,
        CategoryService $categoryService,
        ApiService $apiService
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
