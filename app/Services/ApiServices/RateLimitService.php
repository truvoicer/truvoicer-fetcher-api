<?php

namespace App\Services\ApiServices;

use App\Models\Provider;
use App\Models\ProviderRateLimit;
use App\Models\Sr;
use App\Models\SrRateLimit;
use App\Repositories\ProviderRateLimitRepository;
use App\Repositories\SrRateLimitRepository;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\BaseService;
use Illuminate\Database\Schema\Blueprint;

class RateLimitService extends BaseService
{
    private SrRateLimitRepository $srRateLimitRepository;
    private ProviderRateLimitRepository $providerRateLimitRepository;
    private SrService $srService;

    public function __construct(
        SrService $srService
    )
    {
        parent::__construct();
        $this->srRateLimitRepository = new SrRateLimitRepository();
        $this->providerRateLimitRepository = new ProviderRateLimitRepository();
        $this->srService = $srService;
    }

    public function findBySr(Sr $serviceRequest)
    {
        return $this->srRateLimitRepository->findRateLimitBySr($serviceRequest);
    }

    public function findParentOrChildRateLimitBySr(Sr $serviceRequest) {
        $parentServiceRequest = $this->srService->findParentSr($serviceRequest);
        if (!$parentServiceRequest instanceof Sr) {
            return $this->findBySr($serviceRequest);
        }
        if (empty($serviceRequest->pivot) || empty($serviceRequest->pivot->rate_limits_override)) {
            return $this->findBySr($parentServiceRequest);
        }
        return $this->findBySr($serviceRequest);
    }
    public function createSrRateLimit(Sr $serviceRequest, array $data)
    {
        return $this->srRateLimitRepository->createSrRateLimit($serviceRequest, $data);
    }
    public function saveSrRateLimit(SrRateLimit $srSchedule, array $data)
    {
        return $this->srRateLimitRepository->saveSrRateLimit($srSchedule, $data);
    }

    public function deleteSrRateLimit(SrRateLimit $srSchedule)
    {
        return $this->srRateLimitRepository->deleteSrRateLimit($srSchedule);
    }
    public function createProviderRateLimit(Provider $serviceRequest, array $data)
    {
        return $this->providerRateLimitRepository->createProviderRateLimit($serviceRequest, $data);
    }
    public function saveProviderRateLimit(ProviderRateLimit $srSchedule, array $data)
    {
        return $this->providerRateLimitRepository->saveProviderRateLimit($srSchedule, $data);
    }

    public function deleteProviderRateLimit(ProviderRateLimit $srSchedule)
    {
        return $this->providerRateLimitRepository->deleteProviderRateLimit($srSchedule);
    }

    public function getSrRateLimitRepository(): SrRateLimitRepository
    {
        return $this->srRateLimitRepository;
    }

    public function getProviderRateLimitRepository(): ProviderRateLimitRepository
    {
        return $this->providerRateLimitRepository;
    }

    public static function generateRateLimitTable(Blueprint $table) {
        $table->id();
        $table->integer('max_attempts')->nullable();
        $table->integer('decay_seconds')->nullable();
        $table->integer('delay_seconds_per_request')->nullable();
        $table->timestamps();
    }
}
