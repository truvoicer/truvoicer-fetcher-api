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
        private CategoryService $categoryService,
        protected AccessControlService $accessControlService
    ) {
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
                    'name' => 'no_children',
                    'label' => 'No Children',
                    'source' => 'categories',
                    'dest' => 'categories',
                ],
                [
                    'name' => 'include_children',
                    'label' => 'Include Children',
                    'source' => 'categories',
                    'dest' => 'categories',
                ],
            ],
        ]);
        parent::__construct($accessControlService, new Category());
    }

    public function import(array $data, array $mappings = []): array {
        return array_map(function (array $category) {
            if(!$this->categoryService->getCategoryRepository()->saveCategory($category)) {
                throw new BadRequestHttpException(sprintf("Category id:%s not found in database.",
                    $category->id
                ));
            }
//            $this->userCategoryRepository->createUserCategory($this->user, $createCategory);
            return $this->categoryService->getCategoryRepository()->getModel();
        }, $data);
    }

    public function getImportMappings(array $data) {
        return [];
    }

    public function validateImportData(array $data): void {
        $this->compareKeysWithModelFields($data);
    }

    public function filterImportData(array $data): array {
        $filter = array_filter($data, function ($category) {
            return $this->compareItemKeysWithModelFields($category);
        });

        return [
            'type' => 'categories',
            'data' => $this->parseEntityBatch($filter)
        ];
    }

    public function getExportData(): array {
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

    public function parseEntity(array $entity): array {
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
