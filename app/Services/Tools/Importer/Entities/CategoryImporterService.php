<?php
namespace App\Services\Tools\Importer\Entities;

use App\Models\Category;
use App\Services\Category\CategoryService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CategoryImporterService extends ImporterBase
{

    public function __construct(
        private CategoryService $categoryService,
    ) {
        parent::__construct(new Category());
    }

    public function import(array $data, array $mappings = []) {
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

    public function validateImportData(array $data): bool {
        return $this->compareKeysWithModelFields($data);
    }
}
