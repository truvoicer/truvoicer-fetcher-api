<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\S;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\Permission\AccessControlService;

class SrScheduleImporterService extends ImporterBase
{

    public function __construct(
        private SrScheduleService $srScheduleService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            'name' => 'sr_schedule',
            'import_mappings' => [],
        ]);
        parent::__construct($accessControlService, new S());
    }

    public function import(array $data, array $mappings = [])
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
        return $data;
    }

    public function getSrScheduleService(): SrScheduleService
    {
        return $this->srScheduleService;
    }

}
