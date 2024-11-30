<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\S;
use App\Models\Sr;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;
use Illuminate\Database\Eloquent\Model;

class SrScheduleImporterService extends ImporterBase
{

    public function __construct(
        private SrScheduleService $srScheduleService,
        private SrService $srService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new S());
    }

    protected function loadDependencies(): void
    {
        $this->srService->setThrowException(false);
        $this->srScheduleService->setThrowException(false);
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            false,
            'id',
            ImportType::SR_SCHEDULE->value,
            'Sr Schedules',
            null,
            null,
            null,
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import sr schedule to sr',
                'dest' => ImportType::SR->value,
                'required_fields' => ['id'],
            ],
        ];
    }

    protected function overwrite(array $data, bool $withChildren): array
    {
        return $this->create($data, $withChildren);
    }

    protected function create(array $data, bool $withChildren): array
    {
        $sr = $this->findSr($data);
        if (!$sr['success']) {
            return $sr;
        }
        $sr = $sr['sr'];
        if (!$this->srScheduleService->createSrSchedule($this->getUser(), $sr, $data)) {
            return [
                'success' => false,
                'message' => "Failed to create sr schedule."
            ];
        }
        return [
            'success' => true,
            'message' => "Sr schedule for Sr {$sr->name} imported successfully."
        ];
    }

    public function findSr(array $data): array
    {
        if (!empty($data['sr'])) {
            $sr = $data['sr'];
        } elseif (!empty($data['sr_id'])) {
            $sr = $this->srService->getServiceRequestById((int)$data['sr_id']);
        } else {
            return [
                'success' => false,
                'message' => "Sr is required for sr schedule."
            ];
        }
        if (!$sr instanceof Sr) {
            return [
                'success' => false,
                'message' => "Sr not found for sr schedule."
            ];
        }
        return [
            'success' => true,
            'sr' => $sr
        ];
    }
    public function importSelfNoChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, false);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data): array {
        return $this->importSelf($action, $map, $data, true);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
    public function validateImportData(array $data): void {

    }

    public function filterImportData(array $data): array {
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => [$this->parseEntity($data)]
        ];
    }
    public function parseEntity(array $entity): array {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
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
