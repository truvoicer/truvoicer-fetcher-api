<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\User;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Provider\ProviderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
{
    const RESPONSE_PROPERTIES = [
        'status',
        'contentType',
        'provider',
        'requestService',
        'category',
    ];
    private ApiResponse $apiResponse;

    public function __construct(
        private ApiRequestService       $requestOperation,
        private MongoDBRepository       $mongoDBRepository,
        private ProviderService         $providerService,
        private ApiRequestSearchService $apiRequestSearchService,
        private SrService               $srService,
        private Provider                $provider,
        private Sr                      $sr,
        private User                    $user,
    )
    {
        $this->apiResponse = new ApiResponse();
    }

    public function runSearch(string $providerName, string $srName, ?array $query = []): ApiResponse|false
    {
        $findProvider = $this->findProviderByName($providerName);
        if (!$findProvider instanceof Provider) {
            return false;
        }
        $this->setProvider($findProvider);
        $this->apiRequestSearchService->setProvider($findProvider);
        $sr = $this->findSrByName($srName);
        if (!$sr instanceof Sr) {
            return false;
        }
        $this->setSr($sr);

        $this->apiRequestSearchService->setSr($this->sr);

        return $this->buildResponse(
            $this->apiRequestSearchService->runSearch($query)
        );
    }

    private function buildResponse(LengthAwarePaginator|Collection $results)
    {
        if ($results->isEmpty()) {
            $this->apiResponse->setStatus('error');
            $this->apiResponse->setMessage('No results found');
            return $this->apiResponse;
        }
        $rc = new \ReflectionClass(ApiResponse::class);
        $responseVars = array_map(function ($var) {
            return $var->getName();
        }, $rc->getProperties(\ReflectionProperty::IS_PUBLIC));

        $filterResults = $results->map(function ($result, $index) use ($responseVars) {
            return array_filter($result, function ($value, $key) use ($responseVars) {
                return !in_array($key, $responseVars);
            }, ARRAY_FILTER_USE_BOTH);
        });
        $filterResponseProps = [];
        foreach ($results as $item) {
            $filter = array_filter($item, function ($value, $key) {
                return in_array($key, self::RESPONSE_PROPERTIES);
            }, ARRAY_FILTER_USE_BOTH);

            if (count($filter) === count(self::RESPONSE_PROPERTIES)) {
                $filterResponseProps = $item;
                break;
            }
        }

        $this->apiResponse->setRequestType('search');
        $this->apiResponse->setCategory($filterResponseProps['category']);
        $this->apiResponse->setProvider($filterResponseProps['provider']);
        $this->apiResponse->setMessage('Search Results');
        $this->apiResponse->setContentType($filterResponseProps['contentType']);
        $this->apiResponse->setRequestService($filterResponseProps['requestService']);
        $this->apiResponse->setExtraData([]);
        $this->apiResponse->setStatus('success');
        $this->apiResponse->setRequestData($filterResults->toArray());
        return $this->apiResponse;
    }


    private function findProviderByName(string $providerName): Provider|bool
    {
        $provider = $this->providerService->getUserProviderByName($this->user, $providerName);
        if (!$provider instanceof Provider) {
            return false;
        }
        return $provider;
    }

    private function findSrByName(string $srName): Sr|false
    {
        $sr = $this->srService->getRequestByName($this->provider, $srName);
        if (!$sr instanceof Sr) {
            return false;
        }
        return $sr;
    }

    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
    }

    public function setSr(Sr $sr): void
    {
        $this->sr = $sr;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
