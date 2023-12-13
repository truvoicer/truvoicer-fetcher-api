<?php
namespace App\Services\ApiServices;

use App\Models\Service;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;

class ApiServiceImporterService extends ApiService
{

    public function __construct(HttpRequestService $httpRequestService,
                                ResponseKeysService $responseKeysService,
                                AccessControlService $accessControlService) {
        parent::__construct($httpRequestService, $responseKeysService, $accessControlService);
    }

    public function import(array $data, array $mappings = []) {
        return array_map(function (Service $service) {
            $this->serviceRepository->setModel($service);
            return $this->serviceRepository->save($service);
        }, $data);
    }

    public function getImportMappings(array $data) {
        return [];
    }
}
