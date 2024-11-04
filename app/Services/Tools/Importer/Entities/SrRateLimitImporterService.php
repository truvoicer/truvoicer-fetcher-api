<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\RateLimitService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrRateLimitImporterService extends ImporterBase
{

    public function __construct(
        private RateLimitService       $rateLimitService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            'name' => 'rate_limit',
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new S());
    }

    public function import(array $data, array $mappings = []): array
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

    public function validateImportData(array $data): void
    {

    }

    public function filterImportData(array $data): array
    {
        return [
            'type' => 'rate_limit',
            'data' => $this->parseEntity($data)
        ];
    }

    public function parseEntity(array $entity): array
    {
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getRateLimitService(): RateLimitService
    {
        return $this->rateLimitService;
    }

    public function getExportData(): array
    {
        return [];
    }

    public function getExportTypeData($item): array|bool
    {
        return false;
    }
}
