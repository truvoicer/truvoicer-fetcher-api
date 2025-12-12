<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Enums\Sr\SrType;
use App\Events\ProcessSrOperationDataEvent;
use App\Http\Resources\ApiDirectSearchListCollection;
use App\Http\Resources\ApiSearchItemResource;
use App\Models\Sr;
use App\Paginators\CollectionPaginator;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\Category\CategoryService;
use App\Services\Provider\ProviderService;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
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
    ) {
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
    ) {
        $data = $this->getRequestData();
        // if (!count($providers)) {
        //     return false;
        // }
        $this->prepareProviders($providers, $serviceType);

        if ($this->providers->count() === 0) {
            throw new Exception("Providers not found");
        }

        $collection = new Collection();
        $totalItems = 0;
        foreach ($this->providers as $index => $provider) {

            foreach ($provider->sr as $index => $sr) {
                $response = $this->searchOperationBySr($sr);

                if (!$response) {
                    continue;
                }
                $requestData = $response->getRequestData();
                $extraData = $response->getExtraData();
                if (!empty($extraData[DataConstants::TOTAL_ITEMS])) {
                    $totalItems = $totalItems + (int)$extraData[DataConstants::TOTAL_ITEMS];
                } else {
                    $totalItems = count($requestData);
                }
                switch ($sr->type) {
                    case SrType::LIST:
                        foreach ($requestData as $item) {
                            $collection->add($item);
                        }
                        break;
                    case SrType::DETAIL:
                    case SrType::SINGLE:
                        return new ApiSearchItemResource($requestData);
                }
            }
        }

        switch ($serviceType) {
            case SrType::MIXED->value:
            case SrType::LIST->value:
                $paginator = new ApiDirectSearchListCollection(
                    new CollectionPaginator(
                        $collection,
                        $totalItems,
                        array_key_exists(DataConstants::PAGE_SIZE, $data) ? (int)$data[DataConstants::PAGE_SIZE] : 20,
                        array_key_exists(DataConstants::PAGE_NUMBER, $data) ? (int)$data[DataConstants::PAGE_NUMBER] : 1,
                    )
                );
                return $paginator;
        }
        return false;
    }

    public function searchOperationBySr(
        Sr         $sr,
    ): ApiResponse|bool {
        $provider = $sr->provider;
        $this->apiRequestService->setProvider($provider);

        if ($this->user->cannot('view', $provider)) {
            return false;
        }

        $this->apiRequestService->setSr($sr);

        $response = $this->apiRequestService->runOperation($this->requestData);

        if ($response->getStatus() !== 'success') {
            return false;
        }
        if (!$this->afterFetchOperation($sr, $response)) {
        }
        return $response;
    }

    private function afterFetchOperation(
        Sr          $sr,
        ApiResponse $apiResponse,
    ): bool {
        ProcessSrOperationDataEvent::dispatch(
            $this->user->id,
            $sr->id,
            $apiResponse,
            $this->requestData,
            false,
            true
        );

        return true;
    }
}
