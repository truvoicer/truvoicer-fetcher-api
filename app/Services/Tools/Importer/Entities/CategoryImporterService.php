<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\Category;
use App\Models\Sr;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CategoryImporterService extends ImporterBase
{

    public function __construct(
        private CategoryService        $categoryService,
        protected AccessControlService $accessControlService
    )
    {
        $this->setConfig([
            "show" => true,
            "name" => "categories",
            "id" => "id",
            "label" => "Categories",
            "nameField" => "name",
            "labelField" => "label",
            'children_keys' => [],
            'import_mappings' => [
                [
                    'name' => 'category',
                    'label' => 'Import category',
                    'source' => 'categories',
                    'dest' => 'root',
                    'required_fields' => ['id', 'name', 'label'],
                ],
            ],
        ]);
        parent::__construct($accessControlService, new Category());
    }

    public function import(array $data, array $mappings = []): array
    {
        return array_map(function (array $map) {
            return match ($map['mapping']['name']) {
                'category' => $this->importCategory($this->filterMapData($map)),
                default => [
                    'success' => false,
                    'data' => $map['data'],
                ],
            };
        }, $mappings);
    }

    public function importCategory(array $data): array
    {
        try {
            $this->categoryService->createCategory(
                $this->getUser(),
                $data
            );
            return [
                'success' => true,
                'data' => $this->categoryService->getCategoryRepository()->getModel()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $data,
                'error' => $e->getMessage()
            ];
        }
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
            'import_type' => 'categories',
            'label' => 'Categories',
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
        $entity['import_type'] = 'categories';
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
