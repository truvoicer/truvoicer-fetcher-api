<?php
namespace App\Services\Category;

use App\Models\Category;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CategoryImporterService extends CategoryService
{

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, AccessControlService $accessControlService) {
        parent::__construct($entityManager, $httpRequestService, $tokenStorage, $accessControlService);
    }

    public function import(array $data, array $mappings = []) {
        return array_map(function (Category $category) {
            $createCategory = $this->categoryRepository->saveCategory($category);
            if(isset($createCategory["status"]) && $createCategory["status"] === "error") {
                throw new BadRequestHttpException($createCategory["message"]);
            }
            $this->userCategoryRepository->createUserCategory($this->user, $createCategory);
            return $createCategory;
        }, $data);
    }

    public function getImportMappings(array $data) {
        return [];
    }
}
