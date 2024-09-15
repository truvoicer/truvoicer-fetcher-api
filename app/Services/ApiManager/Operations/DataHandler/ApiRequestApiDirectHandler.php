<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Events\ProcessSrOperationDataEvent;
use App\Models\Sr;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestApiDirectHandler extends ApiRequestDataHandler
{

    public function __construct(
        protected ApiRequestService   $apiRequestService,
        protected EloquentCollection  $providers,
        protected ProviderService     $providerService,
        protected CategoryService     $categoryService,
        protected ApiService          $apiService,
        protected ApiResponse         $apiResponse,
        protected SrOperationsService $srOperationsService,
    )
    {
        parent::__construct(
            $providers,
            $providerService,
            $categoryService,
            $apiService,
            $apiResponse,
            $apiRequestService
        );
    }

    public function searchOperation(
        string $serviceType,
        array  $providers,
        string $serviceName,
        ?array $data = []
    )
    {
        if (!count($providers)) {
            return false;
        }
        $this->prepareProviders($providers, $serviceType);
        if ($this->providers->count() === 0) {
            throw new BadRequestHttpException("Providers not found");
        }

        $collection = new Collection();
        foreach ($this->providers as $index => $provider) {
            foreach ($provider->sr as $sr) {
                $requestData = $this->searchOperationBySr($sr, $data);
                switch ($sr->type) {
                    case SrRepository::SR_TYPE_LIST:
                        foreach ($requestData as $item) {
                            $collection->add($item);
                        }
                        break;
                    case SrRepository::SR_TYPE_DETAIL:
                    case SrRepository::SR_TYPE_SINGLE:
                        return $requestData;
                }
            }
        }

        return $collection;
    }

    public function searchOperationBySr(
        Sr         $sr,
        array      $data
    )
    {
        $provider = $sr->provider;
        $this->apiRequestService->setProvider($provider);
        if ($this->user->cannot('view', $provider)) {
            return false;
        }

        $this->apiRequestService->setSr($sr);

        $response = $this->apiRequestService->runOperation($data);

        if ($response->getStatus() !== 'success') {
            return false;
        }
        if (!$this->afterFetchOperation($sr, $response, $data)) {

        }
        $collection = new Collection();
        switch ($sr->type) {
            case SrRepository::SR_TYPE_LIST:
                foreach ($response->getRequestData() as $item) {
                    $collection->add($item);
                }
                return $collection;
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
                return $response->getRequestData();
            default:
                return false;
        }
    }

    private function afterFetchOperation(
        Sr          $sr,
        ApiResponse $apiResponse,
        array       $data
    ): bool
    {
        ProcessSrOperationDataEvent::dispatch(
            $this->user->id,
            $sr->id,
            $apiResponse,
            $data,
            false,
            true
        );

        return true;
    }
}
