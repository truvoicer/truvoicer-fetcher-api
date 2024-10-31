<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\RateLimitService;

class SrRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private RateLimitService $rateLimitService,
    )
    {
        parent::__construct(new S());
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (S $service) {
            $this->rateLimitService->getSrRateLimitRepository()->setModel($service);
            return $this->rateLimitService->getSrRateLimitRepository()->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {

    }

    public function filterImportData(array $data): array {
        return $data;
    }

    public function getRateLimitService(): RateLimitService
    {
        return $this->rateLimitService;
    }

}
