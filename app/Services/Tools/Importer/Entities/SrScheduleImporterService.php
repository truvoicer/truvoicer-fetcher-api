<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrScheduleImporterService extends ImporterBase
{

    public function __construct(
        private SrScheduleService $srScheduleService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            'name' => 'sr_schedule',
            "label" => "Sr Schedules",
            "id" => "id",
            "nameField" => "name",
            "labelField" => "label",
            'import_mappings' => [
                [
                    'name' => 'no_children',
                    'label' => 'No Children',
                    'source' => 'sr_schedule',
                    'dest' => 'sr_schedule',
                ],
                [
                    'name' => 'include_children',
                    'label' => 'Include Children',
                    'source' => 'sr_schedule',
                    'dest' => 'sr_schedule',
                ],
            ],
        ]);
        parent::__construct($accessControlService, new S());
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (S $service) {
            $this->srScheduleService->getServiceRequestRepository()->setModel($service);
            return $this->srScheduleService->getServiceRequestRepository()->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {

    }

    public function filterImportData(array $data): array {
        return [
            'type' => 'sr_schedule',
            'data' => $this->parseEntity($data)
        ];
    }
    public function parseEntity(array $entity): array {
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getSrScheduleService(): SrScheduleService
    {
        return $this->srScheduleService;
    }

    public function getExportData(): array
    {
        return [];
    }

    public function getExportTypeData($item): array|bool
    {
        return [];
    }
}
