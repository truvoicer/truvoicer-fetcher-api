<?php

namespace App\Services\Tools\Importer\Entities;

use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Repositories\SrRepository;
use App\Services\ApiServices\ApiService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Category\CategoryService;
use App\Services\Permission\AccessControlService;
use App\Services\Permission\PermissionService;
use App\Services\Property\PropertyService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\IExport\IExportTypeService;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProviderImporterService extends ImporterBase
{
    private SrRepository $srRepository;
    public function __construct(
        private ProviderService      $providerService,
        private PropertyService      $propertyService,
        private CategoryService      $categoryService,
        private ApiService           $apiService,
        private SResponseKeysService $responseKeysService,
        private SrImporterService    $srImporterService,
        protected AccessControlService $accessControlService
    ) {
        parent::__construct($accessControlService, new Provider());
        $this->setConfig([
            "show" => true,
            "name" => "providers",
            "id" => "id",
            "label" => "Providers",
            "nameField" => "name",
            "labelField" => "label",
            'children_keys' => ['sr', 'srs'],
            'import_mappings' => [
                [
                    'name' => 'provider_no_children',
                    'label' => 'Import provider (no children)',
                    'source' => 'providers',
                    'dest' => 'root',
                    'required_fields' => ['id', 'name', 'label'],
                ],
                [
                    'name' => 'provider_include_children',
                    'label' => 'Import provider (including children)',
                    'source' => 'providers',
                    'dest' => 'root',
                    'required_fields' => ['id', 'name', 'label'],
                ],
            ],
        ]);
        $this->srRepository = new SrRepository();
    }

    public function import(array $data, array $mappings = []): array
    {
        return [];
    }

    public function validateImportData(array $data): void {
        foreach ($data as $provider) {
            if (empty($provider['name'])) {
                $this->addError(
                    'import_type_validation',
                    "Provider name is required."
                );
            }
            if (empty($provider['label'])) {
                $this->addError(
                    'import_type_validation',
                    "Provider label is required."
                );
            }
            if (
                !empty($data['srs']) &&
                is_array($data['srs'])
            ) {
                $this->srImporterService->validateImportData($data['srs']);
            }
        }
    }
    public function filterData(array $data): array {
        $filterProviders = array_filter($data, function ($provider) {
            return (
                !empty($provider['name']) &&
                !empty($provider['label'])
            );
        }, ARRAY_FILTER_USE_BOTH);

        return array_map(function ($provider) {
            if (
                !empty($provider['srs']) &&
                is_array($provider['srs'])
            ) {
                $provider['srs'] = $this->srImporterService->filterData($provider['srs']);
            }
            return $provider;
        }, $filterProviders);
    }
    public function filterImportData(array $data): array {
        return [
            'root' => true,
            'import_type' => 'providers',
            'label' => 'Providers',
            'children' => $this->parseEntityBatch(
                $this->filterData($data)
            )
        ];
    }

    public function getExportData(): array {
        return $this->providerService->findProviders(
            $this->getUser()
        )->toArray();
    }

    public function getExportTypeData($item): array|bool
    {
        $srs = (!empty($item["srs"]) && is_array($item["srs"])) ?
            $item["srs"] : [];
        $this->providerService->getProviderRepository()->setWith([
            'srs' => function ($query) use ($srs) {
                $query->whereIn('id', array_column($srs, 'id'));

                $childSrs = [];
                foreach ($srs as $sr) {
                    if (is_array($sr['child_srs'])) {
                        $childSrs = array_merge($childSrs, $sr['child_srs']);
                    }
                }
                $query = $this->srRepository->buildNestedSrQuery(
                    $query,
                    $childSrs,
                    [
                        'srConfig' => function ($query) {
                            $query->with('property');
                        },
                        'srParameter',
                        'srSchedule',
                        'srRateLimit',
                        'srResponseKeys',
                        's',
                        'category'
                    ]
                );
            },
            'categories'
        ]);
        $provider = $this->providerService->getProviderRepository()->findById(
            $item["id"],
        );

        if (!$provider) {
            return false;
        }
        if ($this->accessControlService->inAdminGroup()) {
            return $provider->toArray();
        }

        $isPermitted = $this->accessControlService->checkPermissionsForEntity(
            $provider,
            [
                PermissionService::PERMISSION_ADMIN,
                PermissionService::PERMISSION_READ,
            ],
            false
        );
        return $isPermitted ? $provider->toArray() : false;

    }

    public function parseEntity(array $entity): array {
        if (
            !empty($entity['srs']) &&
            is_array($entity['srs'])
        ) {
            $entity['srs'] = [$this->srImporterService->filterImportData($entity['srs'])];
        }
        $entity['import_type'] = 'providers';
        return $entity;
    }

    public function parseEntityBatch(array $data): array
    {
        return array_map(function ($providerData) {
            return $this->parseEntity($providerData);
        }, $data);
    }

    public function getProviderService(): ProviderService
    {
        return $this->providerService;
    }
}
