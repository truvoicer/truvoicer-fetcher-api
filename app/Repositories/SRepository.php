<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\S;
use App\Models\User;

class SRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(S::class);
    }

    public function getModel(): S
    {
        return parent::getModel();
    }

    public function getAllServicesArray() {
        return $this->findAll();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    public function saveService(array $data)
    {
        return $this->save($data);
    }

    public function findByParams(string $sort, string  $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function deleteService(S $service) {
        $this->setModel($service);
        return $this->delete();
    }


    public function userHasEntityPermissions(User $user, S $service, array $permissions)
    {
        $this->setPermissions($permissions);
        $checkCategory = $this->findUserModelBy(new S(), $user, [
            ['s.id', '=', $service->id]
        ]);

        return ($checkCategory instanceof S);
    }

    public function getUserPermissions(User $user, S $service)
    {
        $providerUserId = $user->providers()
            ->where('provider_id', '=', $service->id)
            ->withPivot('id')
            ->first()
            ->getOriginal('pivot_id');
        if (!$providerUserId) {
            return null;
        }

        $providerUserRepo = new ProviderUserRepository();
        $providerUser = $providerUserRepo->findById($providerUserId);
        if (!$providerUser) {
            return null;
        }
        return $this->getResults($providerUser->permissions());
    }

    public function getPermissionsListByUser(User $user, string $sort, string $order, ?int $count) {

        return null;
    }

    public function deleteUserPermissions(User $user, Provider $provider)
    {
        return null;
    }
}
