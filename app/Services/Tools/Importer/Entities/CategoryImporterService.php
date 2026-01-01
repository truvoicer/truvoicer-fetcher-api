<?php

namespace App\Services\Tools\Importer\Entities;

use App\Enums\Import\ImportAction;
use App\Enums\Import\ImportConfig;
use App\Enums\Import\ImportMappingType;
use App\Enums\Import\ImportType;
use Truvoicer\TfDbReadCore\Helpers\Tools\UtilHelpers;
use Truvoicer\TfDbReadCore\Models\Category;
use Truvoicer\TfDbReadCore\Services\Category\CategoryService;
use Truvoicer\TfDbReadCore\Services\Permission\AccessControlService;
use Truvoicer\TfDbReadCore\Services\Permission\PermissionService;
use App\Services\Tools\IExport\IExportTypeService;
use Exception;
use Illuminate\Support\Facades\Log;

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

    protected function loadDependencies(): void
    {
        $this->categoryService->setThrowException(false);
    }

    public function lock(ImportAction $action, array $map, array $data, ?array $dest = null): array
    {
//        $category = $this->categoryService->getCategoryRepository()->findByName(
//            $data['name']
//        );

        $category = $this->categoryService->getUserCategoryRepository()
            ->findUserModelBy(
                new Category(),
                $this->getUser(),
                [['name', '=', $data['name']]
        ], false);

        if (!$category instanceof Category) {
            return [
                'success' => false,
                'message' => "Category {$data['name']} not found."
            ];
        }

        if (!$this->entityService->lockEntity($this->getUser(), $category->id, Category::class)) {
            return [
                'success' => false,
                'message' => "Failed to lock category {$data['name']}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Category is locked.'
        ];
    }

    public function unlock(Category $category): array
    {
        if (!$this->entityService->unlockEntity($this->getUser(), $category->id, Category::class)) {
            return [
                'success' => false,
                'message' => "Failed to unlock category {$category->name}."
            ];
        }
        return [
            'success' => true,
            'message' => 'Category is unlocked.'
        ];
    }

    protected function create(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $checkCategory = $this->categoryService->getUserCategoryRepository()->findUserModelBy(
                new Category(),
                $this->getUser(),
                [
                    ['name', '=', $data['name']]
                ],
                false
            );

            if ($checkCategory instanceof Category) {
                return [
                    'success' => false,
                    'message' => "Category {$data['name']} already exists."
                ];
            }
            $this->categoryService->createCategory(
                $this->getUser(),
                $data
            );

            $category = $this->categoryService->getCategoryRepository()->getModel();

            $unlockCategory = $this->unlock($category);
            if (!$unlockCategory['success']) {
                return $unlockCategory;
            }

            return [
                'success' => true,
                'message' => $category
            ];
        } catch (Exception $e) {
            Log::channel(IExportTypeService::LOGGING_NAME)->error(
                $e->getMessage(),
                [
                    'data' => $data
                ]
            );
            return [
                'success' => false,
                'file' => $e->getFile(),
                'message' => $e->getMessage()
            ];
        }
    }

    protected function overwrite(array $data, bool $withChildren, array $map, ?array $dest = null, ?array $extraData = []): array
    {
        try {
            $checkCategory = $this->categoryService->getUserCategoryRepository()->findUserModelBy(
                new Category(),
                $this->getUser(),
                [
                    ['name', '=', $data['name']]
                ],
                false
            );

            if (!$checkCategory instanceof Category) {
                return [
                    'success' => false,
                    'message' => "Category {$data['name']} not found."
                ];
            }
            if (
                !$this->categoryService->updateCategory(
                    $checkCategory,
                    $data
                )
            ) {
                return [
                    'success' => false,
                    'message' => "Failed to update category {$data['name']}."
                ];
            }
            $category = $this->categoryService->getCategoryRepository()->getModel();
            $unlockCategory = $this->unlock($category);
            if (!$unlockCategory['success']) {
                return $unlockCategory;
            }
            return [
                'success' => true,
                'message' => "Category {$data['name']} updated successfully."
            ];
        } catch (Exception $e) {
            Log::channel(IExportTypeService::LOGGING_NAME)->error(
                $e->getMessage(),
                [
                    'data' => $data
                ]
            );
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getImportMappings(array $data): array
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

    public function deepFind(ImportType $importType, array $data, array $conditions, ?string $operation = 'AND'): array|null {
        return UtilHelpers::deepFindInNestedEntity(
            data: $data,
            conditions: $conditions,
            childrenKeys: [],
            itemToMatchHandler: function ($item) use ($importType) {
                return match ($importType) {
                    ImportType::CATEGORY => [$item],
                    default => [],
                };
            },
            operation: $operation
        );
    }

    public function getCategoryService(): CategoryService
    {
        return $this->categoryService;
    }

}
