<?php

namespace App\Repositories;

use App\Helpers\Tools\UtilHelpers;
use App\Models\Category;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrChildSr;
use App\Models\User;
use App\Services\Category\CategoryService;
use App\Services\Permission\PermissionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SrRepository extends BaseRepository
{
    private ProviderRepository $providerRepository;
    private SrChildSrRepository $srChildSrRepository;

    public function __construct()
    {
        parent::__construct(Sr::class);
        $this->providerRepository = new ProviderRepository();
        $this->srChildSrRepository = new SrChildSrRepository();
    }


    public function getModel(): Sr
    {
        return parent::getModel();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    public function buildNestedSrQuery($query, array $srs, ?array $with = []): HasMany|BelongsToMany
    {
        if (!count($srs)) {
            return $query->with($with)->without('childSrs');
        }

        $query->with(['childSrs' => function ($query) use ($srs, $with) {
            $query->whereIn('sr_child_id', array_column($srs, 'id'));
            $childSrs = [];
            foreach ($srs as $sr) {
                if (is_array($sr['child_srs'])) {
                    $childSrs = array_merge($childSrs, $sr['child_srs']);
                }
            }
            $query = $this->buildNestedSrQuery(
                $query,
                $childSrs,
                $with
            );
        }, ...$with]);
        return $query;
    }

    public function findSrsWithSchedule(Provider $provider)
    {
        return $this->getResults(
            $provider->sr()
                ->whereHas('srSchedule')
        );
    }


    public function getUserServiceRequestByIds(User $user, array $ids)
    {
        $this->providerRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        if (is_null($this->query)) {
            $this->setQuery($this->buildQuery());
        }
        return $this->getResults(
            $this->query
                ->whereIn('id', $ids)
                ->whereHas('provider', function ($query) use ($user, $ids) {
                    $query = $this->providerRepository->userPermissionsQuery($user, $query);
                })
        );
    }

    public function getUserServiceRequestByProviderIds(User $user, array $providerIds)
    {
        $this->providerRepository->setPermissions([
            PermissionService::PERMISSION_ADMIN,
            PermissionService::PERMISSION_READ,
        ]);
        if (is_null($this->query)) {
            $this->setQuery($this->buildQuery());
        }
        return $this->getResults(
            $this->query
                ->whereHas('provider', function ($query) use ($user, $providerIds) {
                    $query->whereIn('id', $providerIds);
                    $query = $this->providerRepository->userPermissionsQuery($user, $query);
                })
        );
    }

    public function getServiceRequestByProvider(Provider $provider)
    {
        return $this->getResults(
            $provider->serviceRequest()
                ->orderBy($this->getSortField(), $this->getOrderDir())
                ->with(['category', 's', 'srSchedule', 'srRateLimit'])
                ->without(['childSrs'])
        );
    }

    public function getChildSrs(Sr $sr, string $sort, string $order, ?int $count = null)
    {
        return $this->getResults(
            $sr->childSrs()
                ->orderBy($sort, $order)
                ->with(['category', 's', 'srSchedule', 'srRateLimit'])
                ->without(['childSrs'])
        );
    }

    public static function getSrByName(Provider $provider, string $serviceRequestName)
    {
        return $provider->serviceRequest()
            ->where('name', $serviceRequestName)
            ->first();
    }

    public function buildSaveData(array $data, ?Sr $serviceRequest = null)
    {
        $fields = [
            'name',
            'label',
            'pagination_type',
            'query_parameters',
            'type',
            'default_sr',
            'default_data',
        ];
        $saveData = [];
        $attributes = null;
        if ($serviceRequest instanceof Sr) {
            $attributes = $serviceRequest->getAttributes();
        }
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            } else if (
                $serviceRequest instanceof Sr &&
                array_key_exists($field, $attributes)
            ) {
                $saveData[$field] = $serviceRequest->{$field};
            }
        }
        if (!empty($data['query_parameters']) && !is_array($data['query_parameters'])) {
            unset($saveData['query_parameters']);
        }
        if ($serviceRequest instanceof Sr) {
            $service = $serviceRequest->s()->first();
            if ($service instanceof S) {
                $saveData['service'] = $service->id;
            }
        }
        if ($serviceRequest instanceof Sr) {
            $category = $serviceRequest->category()->first();
            if ($category instanceof Category) {
                $saveData['category'] = $category->id;
            }
        }
        return $saveData;
    }

    public function createServiceRequest(Provider $provider, array $data)
    {
        if (
            !UtilHelpers::isArrayItemNumeric('service', $data) &&
            !(!empty($data['service']) && !$data['service'] instanceof S)
        ) {
            throw new BadRequestHttpException(sprintf(
                "Service id is required for service request: %s | provider id: %s | provider name: %s",
                (!empty($data['name'])) ? $data['name'] : 'N/A',
                $provider->id,
                $provider->name
            ));
        }
        $create = $provider->serviceRequest()->create($this->buildSaveData($data));
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return $this->saveAssociations($create, $data);
    }

    public function saveAssociations(Sr $serviceRequest, array $data)
    {
        if (
            UtilHelpers::isArrayItemNumeric('service', $data) &&
            !$this->associateServiceById($serviceRequest, $data['service'])
        ) {
            throw new BadRequestHttpException('Error saving service association');
        }
        if (
            UtilHelpers::isArrayItemNumeric('category', $data) &&
            !$this->associateCategoryById($serviceRequest, $data['category'])
        ) {
            throw new BadRequestHttpException('Error saving category association');
        }
        return true;
    }

    public function associateServiceById(Sr $serviceRequest, int $serviceId)
    {
        $service = (new SRepository())->findById($serviceId);
        if (!$service instanceof S) {
            return false;
        }
        return $serviceRequest->s()->associate($service)->save();
    }

    public function associateCategoryById(Sr $serviceRequest, int $categoryId)
    {
        $category = (new CategoryRepository())->findById($categoryId);
        if (!$category instanceof Category) {
            return false;
        }
        return $serviceRequest->category()->associate($categoryId)->save();
    }

    public function saveServiceRequest(Sr $serviceRequest, array $data)
    {
        $this->setModel($serviceRequest);
        if (!$this->save($this->buildSaveData($data))) {
            return false;
        }
        return $this->saveAssociations($this->getModel(), $data);
    }

    public function saveChildSrOverrides(Sr $serviceRequest, array $data)
    {
        $this->setModel($serviceRequest);
//        $parentSr = $serviceRequest->parentSrs()->first();
//
//        if (!$parentSr instanceof Sr) {
//            return false;
//        }
        return $this->getModel()->pivot->update($data);
    }

    public function duplicateServiceRequest(Sr $serviceRequest, string $label, string $name, bool $includeChildSrs, ?Sr $parentSr = null)
    {
        $this->setModel($serviceRequest->replicate());
        $clone = $this->getModel();
        $clone->name = $name;
        $clone->label = $label;
        if (!$clone->save()) {
            return false;
        }

        $assoc = [
            'service' => $serviceRequest->s()->first()->id,
            'category' => $serviceRequest->category()->first()->id
        ];
        $this->saveAssociations($clone, $assoc);

        $findParentSr = $serviceRequest->parentSrs()->get();

        if (!$parentSr instanceof Sr) {
            $serviceRequest->srResponseKey()->get()->each(function ($item) use ($clone) {
                $newItem = $item->replicate();
                $clone->srResponseKey()->save($newItem);
            });
            $serviceRequest->srConfig()->get()->each(function ($item) use ($clone) {
                $newItem = $item->replicate();
                $clone->srConfig()->save($newItem);
            });
            $serviceRequest->srParameter()->get()->each(function ($item) use ($clone) {
                $newItem = $item->replicate();
                $clone->srParameter()->save($newItem);
            });
        }
        $findParentSr->each(function ($item) use ($clone) {
            $item->childSrs()->save($clone);
        });
        if ($includeChildSrs) {
            $serviceRequest->childSrs()->get()->each(function ($item) use ($clone) {
                $newItem = $item->replicate();
                $clone->childSrs()->save($newItem);
            });
        }
        if ($parentSr instanceof Sr) {
            $parentSr->childSrs()->save($clone);
        }
        return true;
    }
    private function deleteSingleChildSr(Sr $sr) {
        $childSrs = $sr->childSrs()->get();
        if ($childSrs->count() > 0) {
            $this->deleteChildSrs($childSrs);
        }
        $sr->srChildSr()->delete();
        return $sr->delete();
    }
    private function deleteChildSrs(Collection $srs)
    {
        $srs->each(function (Sr $sr) {
            $this->deleteSingleChildSr($sr);
        });
    }
    public function deleteBatch(array $ids)
    {
        $ids = array_map('intval', $ids);
        $this->addWhere('id', $ids, 'IN');

        $this->deleteChildSrs($this->findMany());
        return true;
    }

    public function delete()
    {
        if (!$this->deleteSingleChildSr($this->model)) {
            $this->addError('repository_delete_error', 'Error deleting listing');
            return false;
        }
        return true;
    }


}
