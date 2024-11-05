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
            "label" => "Rate Limits",
            "id" => "id",
            'import_mappings' => [
                [
                    'name' => 'no_children',
                    'label' => 'No Children',
                    'source' => 'rate_limit',
                    'dest' => 'rate_limit',
                ],
                [
                    'name' => 'include_children',
                    'label' => 'Include Children',
                    'source' => 'rate_limit',
                    'dest' => 'rate_limit',
                ],
            ],
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
            'import_type' => 'rate_limit',
            'label' => 'Rate Limits',
            'children' => [$this->parseEntity($data)]
        ];
    }

    public function parseEntity(array $entity): array
    {
        $entity['import_type'] = 'rate_limit';
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
