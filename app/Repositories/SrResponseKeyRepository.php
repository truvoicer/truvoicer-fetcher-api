<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrResponseKey;
use App\Models\SResponseKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SrResponseKey::class);
    }

    public function getModel(): SrResponseKey
    {
        return parent::getModel();
    }


    public static function getRequestResponseKeyByName(Provider $provider, Sr $serviceRequest, string $keyName)
    {
        return $provider->serviceRequest()
            ->where('id', $serviceRequest->id)
            ->first()
            ->srResponseKeys()
            ->with('srResponseKey')
            ->where('name', $keyName)
            ->first();
    }

    public static function getSrResponseKeyValueByName(Provider $provider, Sr $serviceRequest, string $keyName)
    {
        $responseKey = self::getRequestResponseKeyByName($provider, $serviceRequest, $keyName);
        if (!$responseKey instanceof SResponseKey) {
            return null;
        }
        $value = $responseKey->srResponseKey()->first();
        if (!$value instanceof SrResponseKey) {
            return null;
        }
        return $value->value;
    }

    public function removeAllServiceRequestResponseKeys(Sr $serviceRequest)
    {
        return $serviceRequest->srResponseKey()->delete();
    }

    public function duplicateRequestResponseKeys(
        Sr $sourceServiceRequest,
        Sr $destinationServiceRequest
    ) {
        //        $sourceResponseKeys = $sourceServiceRequest->getServiceRequestResponseKeys();
        //        foreach ($sourceResponseKeys as $item) {
        //            $responseKey = new ServiceRequestResponseKey();
        //            $responseKey->setServiceRequest($destinationServiceRequest);
        //            $responseKey->setServiceResponseKey($item->getServiceResponseKey());
        //            $responseKey->setResponseKeyValue($item->value);
        //            $responseKey->setShowInResponse($item->getShowInResponse());
        //            $responseKey->setHasArrayValue($item->getHasArrayValue());
        //            $responseKey->setArrayKeys($item->getArrayKeys());
        //            $responseKey->setListItem($item->getListItem());
        //            $responseKey->setReturnDataType($item->getReturnDataType());
        //            $responseKey->setAppendExtraData($item->getAppendExtraData());
        //            $responseKey->setAppendExtraDataValue($item->getAppendExtraDataValue());
        //            $responseKey->setPrependExtraData($item->getPrependExtraData());
        //            $responseKey->setPrependExtraDataValue($item->getPrependExtraDataValue());
        //            $destinationServiceRequest->addServiceRequestResponseKey($responseKey);
        //            $this->getEntityManager()->persist($responseKey);
        //        }
        //        $this->getEntityManager()->persist($destinationServiceRequest);
        //        $this->getEntityManager()->flush();
        return null;
    }

    public function mergeRequestResponseKeys(
        Sr $sourceServiceRequest,
        Sr $destinationServiceRequest
    ) {
        //        $this->removeAllServiceRequestResponseKeys($destinationServiceRequest);
        //        $this->duplicateRequestResponseKeys($sourceServiceRequest, $destinationServiceRequest);
        return true;
    }

    public function updateSrResponseKey(SrResponseKey $serviceRequestResponseKey, array $data)
    {
        $this->setModel($serviceRequestResponseKey);
        return $this->save($data);
    }

    public function findSrResponseKeysWithRelationQuery(
        Sr $serviceRequest,
        ?array $excludeKeys = [],
        ?array $conditions = [],
        ?string $orderBy = null,
        ?string $orderDirection = 'asc'
    ): HasMany {
        // Define the relationship name you want to load
        // Based on your original code, it seems to be 'srResponseKey'
        $relationshipToLoad = 'srResponseKey';
        $service = $serviceRequest->s()->first()->sResponseKey();

        // Use select() to avoid column name conflicts (e.g., 'id')
        $service->select('s_response_keys.*');

        // Join the related table
        $service->leftJoin('sr_response_keys', function ($join) use ($serviceRequest) {
            // Define the relationship between the two tables for the join
            // Replace with your actual foreign key relationship
            $join->on('s_response_keys.id', '=', 'sr_response_keys.s_response_key_id')
                ->where('sr_response_keys.sr_id', '=', $serviceRequest->id);
        });

        // Apply your existing conditions
        foreach ($conditions as $key => $value) {
            // Be specific with the table name if column names could clash
            $service->where('s_response_keys.' . $key, '=', $value);
        }
        if (!empty($excludeKeys)) {
            $service->whereNotIn('s_response_keys.name', $excludeKeys);
        }
        if (!empty($orderBy)) {
            // Be specific with the table name if column names could clash
            $service->orderBy($orderBy, $orderDirection ?? 'asc');
        }

        // 4. EAGER LOAD the relationship to get the nested object in the final result
        $service->with([$relationshipToLoad => function ($query) use ($serviceRequest) {
            // You can add constraints to the eager-loaded data here if needed
            // This ensures the loaded relationship is the same one you joined against
            $query->where('sr_id', '=', $serviceRequest->id);
        }]);
        return $service;
    }
    public function findSrResponseKeysWithRelation(
        Sr $serviceRequest,
        ?array $excludeKeys = [],
        ?array $conditions = [],
        ?string $orderBy = null,
        ?string $orderDirection = 'asc'
    ): LengthAwarePaginator|Collection {
        return $this->getResults(
            $this->findSrResponseKeysWithRelationQuery(
                $serviceRequest,
                $excludeKeys,
                $conditions,
                $orderBy,
                $orderDirection
            )
        );
    }
    public function findOneSrResponseKeysWithRelation(Sr $serviceRequest, ?array $excludeKeys = [], ?array $conditions = []): Model|null
    {
        return $this->findSrResponseKeysWithRelationQuery($serviceRequest, $excludeKeys, $conditions)->first();
    }

    public function findServiceRequestResponseKeys(Sr $serviceRequest, string $sort = "name", string $order = "asc", ?int $count = null)
    {
        return $serviceRequest->srResponseKeys()
            ->with('srResponseKey')
            ->orderBy($sort, $order)
            ->paginate();
    }

    public function findServiceRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey)
    {
        $query = SResponseKey::with(['srResponseKey' => function ($query) use ($serviceRequest) {
            $query->where('sr_id', '=', $serviceRequest->id);
            $query->with('srResponseKeySrs', function ($query) use ($serviceRequest) {
                //                $query->where('sr_id', '=', $serviceRequest->id);
            });
        }])
            ->where('id', '=', $serviceResponseKey->id)
            ->first();
        return $query;
    }

    public function createServiceRequestResponseKey(Sr $serviceRequest, string $sResponseKeyName, array $data)
    {
        $sResponseKerRepo = new SResponseKeyRepository();
        $findSResponseKey = $sResponseKerRepo->getServiceResponseKeyByName(
            $serviceRequest->s()->first(),
            $sResponseKeyName
        );

        if ($findSResponseKey instanceof SResponseKey) {
            return $this->saveServiceRequestResponseKey(
                $serviceRequest,
                $findSResponseKey,
                $data
            );
        }
        $sResponseKey = $sResponseKerRepo->createServiceResponseKey(
            $serviceRequest->s()->first(),
            ['name' => $sResponseKeyName]
        );
        if (!$sResponseKey) {
            return false;
        }
        return $this->saveServiceRequestResponseKey(
            $serviceRequest,
            $sResponseKerRepo->getModel(),
            $data
        );
    }

    public function reorderSrResponseKeys(array $orderedIds): bool
    {
        $priority = 0;
        foreach ($orderedIds as $id) {
            $key = $this->findById($id);
            if (!$key instanceof SrResponseKey) {
                throw new BadRequestHttpException("Invalid response key ID: " . $id);
            }
            $key->search_priority = $priority;
            if (!$key->save()) {
                return false;
            }
            $priority++;
        }
        return true;
    }

    public function setResponseKeyAsHighestPriority(
        User $user,
        Sr $serviceRequest,
        SrResponseKey $srResponseKey,
    ) {
        $allKeys = $this->findSrResponseKeysWithRelationQuery(
            $serviceRequest,
            [],
            [],
            'sr_response_keys.search_priority',
            'asc'
        )->get();
        $srResponseKey->search_priority = 0;
        if (!$srResponseKey->save()) {
            return false;
        }
        $priority = 1;
        foreach ($allKeys as $key) {
            if ($key->srResponseKey->id === $srResponseKey->id) {
                continue;
            }
            $key->srResponseKey->search_priority = $priority;
            if (!$key->srResponseKey->save()) {
                return false;
            }
            $priority++;
        }
        return true;
    }

    public function saveServiceRequestResponseKey(
        Sr $serviceRequest,
        SResponseKey $serviceResponseKey,
        array $data
    )
    {
        $find = $this->findServiceRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
        if (!$find->srResponseKey instanceof SrResponseKey) {
            $data = array_map(
                fn($item) => (is_array($item)) ? json_encode($item) : $item,
                $data
            );

            $toggle = $this->dbHelpers->validateToggle(
                $serviceRequest->srResponseKeys()->toggle(
                    [$serviceResponseKey->id => $data]
                ),
                [$serviceResponseKey->id]
            );
            $find = $this->findServiceRequestResponseKeyByResponseKey($serviceRequest, $serviceResponseKey);
            $this->setModel($find->srResponseKey);
        }
        $this->setModel($find->srResponseKey);
        $update = $serviceRequest->srResponseKeys()->updateExistingPivot($serviceResponseKey->id, $data);
        return true;
    }

    public function deleteServiceRequestResponseKeyByResponseKey(Sr $serviceRequest, SResponseKey $serviceResponseKey)
    {
        return ($serviceRequest->srResponseKeys()->detach($serviceResponseKey->id) > 0);
    }

    public function deleteRequestResponseKeys(SrResponseKey $requestResponseKey)
    {
        $this->setModel($requestResponseKey);
        return $this->delete();
    }
}
