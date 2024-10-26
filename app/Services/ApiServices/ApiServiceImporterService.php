<?php

namespace App\Services\ApiServices;

use App\Models\S;
use App\Repositories\SRepository;
use App\Repositories\SrParameterRepository;
use App\Repositories\SrRepository;
use App\Services\Permission\AccessControlService;
use App\Services\Tools\HttpRequestService;

class ApiServiceImporterService extends ApiService
{

    public function __construct(
        protected SResponseKeysService  $responseKeysService,
        protected AccessControlService  $accessControlService,
        protected SRepository           $serviceRepository,
        protected SrRepository          $serviceRequestRepository,
        protected SrParameterRepository $requestParametersRepo,

    )
    {
        parent::__construct($responseKeysService, $accessControlService, $serviceRepository, $serviceRequestRepository, $requestParametersRepo);
    }

    public function import(array $data, array $mappings = [])
    {
        return array_map(function (S $service) {
            $this->serviceRepository->setModel($service);
            return $this->serviceRepository->save($service);
        }, $data);
    }

    public function getImportMappings(array $data)
    {
        return [];
    }
}
