<?php
namespace App\Service;

use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SearchService extends BaseService
{
    private $entityManager;
    private $httpRequestService;
    private $providerService;
    private $requestService;
    private $categoryService;
    private $apiService;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                ProviderService $providerService, RequestService $requestService,
                                CategoryService $categoryService, ApiService $apiService,
                                TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->entityManager = $entityManager;
        $this->httpRequestService = $httpRequestService;
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
