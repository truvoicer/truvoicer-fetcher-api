<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use App\Models\Category;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;

class CategoryImporterService extends ImporterBase
{

    public function __construct(
        private CategoryService        $categoryService,
        protected AccessControlService $accessControlService
    )
    {
        parent::__construct($accessControlService, new Category());
    }

    protected function setConfig(): void
    {
        $this->buildConfig(
            true,
            'id',
            ImportType::CATEGORY->value,
            'Categories',
            'name',
            'label',
            'label',
            [],
        );
    }

    protected function setMappings(): void
    {
        $this->mappings = [
            [
                'name' => ImportMappingType::SELF_NO_CHILDREN->value,
                'label' => 'Import category',
                'dest' => null,
                'required_fields' => ['id', 'name', 'label'],
            ],
        ];
    }

    private function createCategory(array $data): array
    {
        try {
            $this->categoryService->createCategory(
                $this->getUser(),
                $data
            );
            return [
                'success' => true,
                'message' => $this->categoryService->getCategoryRepository()->getModel()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    private function overwriteCategory(array $data): array
    {
        try {
            $this->categoryService->createCategory(
                $this->getUser(),
                $data
            );
            return [
                'success' => true,
                'message' => $this->categoryService->getCategoryRepository()->getModel()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'message' => $e->getMessage()
            ];
        }
    }

    public function import(ImportAction $action, array $data, bool $withChildren): array
    {
        switch ($action) {
            case ImportAction::CREATE:
                return $this->createCategory($data);
            case ImportAction::OVERWRITE:
                return $this->overwriteCategory($data);
        }
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

    public function validateImportData(array $data): void
    {
        $this->compareKeysWithModelFields($data);
    }

    public function filterImportData(array $data): array
    {
        $filter = array_filter($data, function ($category) {
            return $this->compareItemKeysWithModelFields($category);
        });
        return [
            'root' => true,
            'import_type' => $this->getConfigItem(ImportConfig::NAME),
            'label' => $this->getConfigItem(ImportConfig::LABEL),
            'children' => $this->parseEntityBatch($filter)
        ];
    }

    public function getExportData(): array
    {
        return $this->categoryService->findUserCategories(
            $this->getUser(),
            false
        )->toArray();
    }

    public function getExportTypeData($item): array|bool
    {
        $category = $this->categoryService->getCategoryById($item["id"]);
        if ($this->accessControlService->inAdminGroup()) {
            return $category->toArray();
        }

        $isPermitted = $this->accessControlService->checkPermissionsForEntity(
            $category,
            [
                PermissionService::PERMISSION_ADMIN,
                PermissionService::PERMISSION_READ,
            ],
            false
        );
        return $isPermitted ? $category->toArray() : false;
    }

    public function parseEntity(array $entity): array
    {
        $entity['import_type'] = $this->getConfigItem(ImportConfig::NAME);
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function (array $providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getCategoryService(): CategoryService
    {
        return $this->categoryService;
    }

}
