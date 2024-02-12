<?php

namespace App\Services\ApiManager\Operations;

use App\Models\Provider;
use App\Models\Sr;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Provider\ProviderService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestDataHandler
{
    public function __construct(
        private MongoDBRepository $mongoDBRepository,
        private ProviderService $providerService,
        private SrService $srService,
        private Provider $provider,
        private Sr $sr,
    )
    {
    }

    public function runSearch(string $providerName, string $srName, ?array $query = []): ApiResponse
    {
        $findProvider = $this->findProviderByName($providerName);
        if (!$findProvider instanceof Provider) {
            return false;
        }
        $sr = $this->findSrByName($srName);
        if (!$sr instanceof Sr) {
            return false;
        }
        $this->setProvider($findProvider);
        $this->setSr($sr);

        $this->mongoDBRepository->setProvider($this->provider);
        $this->mongoDBRepository->setSr($this->sr);
        return $this->mongoDBRepository->find($query);
    }

    private function findProviderByName(string $providerName): Provider|bool
    {
        $provider = $this->providerService->getUserProviderByName($this->getUser(), $providerName);
        if (!$provider instanceof Provider) {
            return false;
        }
        return $provider;
    }
    private function findSrByName(string $srName): Sr
    {
        $sr = $this->srService->getRequestByName($this->provider, $srName);
        if (!$sr instanceof Sr) {
            throw new BadRequestHttpException("Service request doesn't exist, check config.");
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

}
