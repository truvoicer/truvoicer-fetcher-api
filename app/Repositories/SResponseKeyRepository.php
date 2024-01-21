<?php

namespace App\Repositories;

use App\Library\Defaults\DefaultData;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiServices\ResponseKeysService;
use Illuminate\Database\Eloquent\Model;

class SResponseKeyRepository extends BaseRepository
{
    private SrResponseKeyRepository $srResponseKeyRepository;
    public function __construct()
    {
        parent::__construct(SResponseKey::class);
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
    }

    public function getModel(): SResponseKey
    {
        return parent::getModel();
    }

    public function findById(int $id): ?SResponseKey
    {
        return parent::findById($id); // TODO: Change the autogenerated stub
    }


    public function getResponseKeysByRequest(Provider $provider, Sr $serviceRequest)
    {
        return $this->srResponseKeyRepository->findSrResponseKeysWithRelation($serviceRequest);
    }

    public function createDefaultServiceResponseKeys(S $service, ?string $contentType = 'json', ?bool $requiredOnly = false) {
        $errors = [];
        $defaultResponseKeys = DefaultData::getServiceResponseKeys($contentType);
        if ($requiredOnly) {
            $defaultResponseKeys = array_filter($defaultResponseKeys, function ($item) {
                return $item[ResponseKeysService::RESPONSE_KEY_REQUIRED];
            });
        }
        $defaultResponseKeyNames = array_column($defaultResponseKeys, ResponseKeysService::RESPONSE_KEY_NAME);
        $findByNames = $this->findServiceResponseKeysByNameBatch(
            $service,
            $defaultResponseKeyNames
        );
        $diff = array_diff(
            $defaultResponseKeyNames,
            array_column($findByNames->toArray(), 'name')
        );
        foreach ($diff as $key) {
            $create = $this->createServiceResponseKey($service, [
                "name" => $key
            ]);
            if (!$create) {
                $errors[] = sprintf("Error creating default response key: %s", $key);
            }
        }
        return count($errors) === 0;
    }
    public function findServiceResponseKeysByNameBatch(S $service, array $names) {
        return $service->sResponseKey()->whereIn('name', $names)->get();
    }
    public function findServiceResponseKeysDiffByNameBatch(S $service, array $names) {
        return $service->sResponseKey()->get()->diff(SResponseKey::whereIn('name', $names)->get());
    }
    public function getServiceResponseKeyByName(S $service, string $name)
    {
        return $service->sResponseKey()->where('name', $name)->first();
    }

    public function findServiceResponseKeys(S $service) {
        return $service->sResponseKey()->get();
    }

    public function createServiceResponseKey(S $service, array $data) {
        $create = $service->sResponseKey()->create($data);
        $this->setModel($create);
        return true;
    }
    public function saveServiceResponseKey(SResponseKey $serviceResponseKey, array $data) {
        $this->setModel($serviceResponseKey);
        return $this->save($data);
    }
}
