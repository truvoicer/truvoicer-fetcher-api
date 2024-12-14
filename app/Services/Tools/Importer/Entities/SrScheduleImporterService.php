<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Sr;
use App\Models\SrSchedule;
use App\Services\ApiServices\ServiceRequests\SrScheduleService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Permission\AccessControlService;

class SrScheduleImporterService extends ImporterBase
{

    public function __construct(
        private SrScheduleService $srScheduleService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new SrSchedule());
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

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        return $this->create($data, $withChildren, $map);
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        $sr = $this->findSr(ImportType::SR_SCHEDULE, $data, $map, $dest);
        if (!$sr['success']) {
            return $sr;
        }
        $data['execute_immediately'] = false;
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

    public function importSelfNoChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array {
        return $this->importSelf($action, $map, $data, false, $dest);
    }

    public function importSelfWithChildren(ImportAction $action, array $map, array $data, ?array $dest = null): array {
        return $this->importSelf($action, $map, $data, true, $dest);
    }

    public function getImportMappings(array $data): array
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

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {

        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: ['srs', 'sr', 'child_srs'],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::SR_SCHEDULE => (!empty($item['sr_schedule']))? [$item['sr_schedule']] : [],
                    default => [],
                };
            },
            operation: $operation
        );
    }

}
