<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiDirectSearchListResourceCollection;
use App\Http\Resources\ApiMongoDBSearchListResourceCollection;
use App\Models\Provider;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestApiDirectHandler extends ApiRequestDataHandler
{

    public function __construct(
        protected ApiRequestService $apiRequestService,
        protected EloquentCollection $srs,
        protected ProviderService    $providerService,
        protected CategoryService $categoryService,
        protected ApiService $apiService,
        protected ApiResponse $apiResponse
    )
    {
        parent::__construct(
            $srs,
            $providerService,
            $categoryService,
            $apiService,
            $apiResponse,
            $apiRequestService
        );
    }

    public function searchOperation(
        string $serviceType,
        array $providers,
        string $serviceName,
        ?array $data = []
    )
    {
        if (!count($providers)) {
            return false;
        }
        $this->buildServiceRequests($providers, $serviceType);
        if ($this->srs->count() === 0) {
            throw new BadRequestHttpException("Providers not found");
        }

        $requestData = new Collection();
        foreach ($this->srs as $index => $sr) {
            $provider = $sr->provider()->first();
            if (!$provider instanceof Provider) {
                return false;
            }

            $this->apiRequestService->setProvider($provider);
            if ($this->user->cannot('view', $provider)) {
                return false;
            }

            $this->apiRequestService->setSr($sr);

            $response = $this->apiRequestService->runOperation($data);

            if ($response->getStatus() !== 'success') {
                continue;
            }
            switch ($sr->type) {
                case SrRepository::SR_TYPE_LIST:
                    foreach ($response->getRequestData() as  $item) {
                        $requestData->add($item);
                    }
                    break;
                case SrRepository::SR_TYPE_DETAIL:
                case SrRepository::SR_TYPE_SINGLE:
                    return $response->getRequestData();
            }
        }

        return new ApiDirectSearchListResourceCollection($requestData);
    }

}
