<?php

namespace App\Services\ApiManager\Operations\DataHandler;

use App\Http\Resources\ApiMongoDBSearchListResourceCollection;
use App\Http\Resources\ApiSearchItemResource;
use App\Models\User;
use App\Repositories\SrRepository;

class ApiRequestDataInterface
{
    private User $user;

    public function __construct(
        private ApiRequestMongoDbHandler $apiRequestMongoDbHandler,
        private ApiRequestApiDirectHandler $apiRequestApiDirectHandler
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
                dd('miced', $providers);
                $response = $this->apiRequestMongoDbHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $data
                );
                $response = $this->apiRequestApiDirectHandler->searchOperation(
                    $serviceType, $providers, $serviceName, $data
                );
                dd($response);
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


    public function setUser(User $user): void
    {
        $this->user = $user;
    }

}
