<?php

namespace App\Services\ApiServices;

use App\Models\Provider;
use App\Models\ProviderRateLimit;
use App\Models\Sr;
use App\Models\SrRateLimit;
use App\Repositories\ProviderRateLimitRepository;
use App\Repositories\SrRateLimitRepository;
use App\Services\BaseService;
use Illuminate\Database\Schema\Blueprint;

class RateLimitService extends BaseService
{
    private SrRateLimitRepository $srRateLimitRepository;
    private ProviderRateLimitRepository $providerRateLimitRepository;

    public function __construct()
    {
        parent::__construct();
        $this->srRateLimitRepository = new SrRateLimitRepository();
        $this->providerRateLimitRepository = new ProviderRateLimitRepository();
    }

    public function findBySr(Sr $serviceRequest)
    {
        return $this->srRateLimitRepository->findRateLimitBySr($serviceRequest);
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
