<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportType;

interface ImporterInterface
{

    public function setConfig(): void;

    public function setMappings(): void;

    public function loadDependencies(): void;

    public function validateImportData(array $data): void;

    public function filterImportData(array $data): array;

    public function getExportData(): array;

    public function getExportTypeData($item): array|bool;

    public function parseEntity(array $entity): array;

    public function parseEntityBatch(array $data): array;

    public function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array;

    public function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array;

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): ?array;
}
