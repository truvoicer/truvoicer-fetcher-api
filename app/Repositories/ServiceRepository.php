<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Service;
use App\Models\User;

class ServiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Service::class);
    }

    public function getModel(): Service
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

    public function getServiceByRequestName(Provider $provider, string $serviceName) {
        return $provider->serviceRequest()->where('name', $serviceName)->first();
    }

    public function findByParams(string $sort, string  $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function deleteService(Service $service) {
        $this->setModel($service);
        return $this->delete();
    }


    public function userHasEntityPermissions(User $user, Service $service, array $permissions)
    {
        $this->setPermissions($permissions);
        $checkCategory = $this->findUserModelBy(new Service(), $user, [
            ['services.id', '=', $service->id]
        ]);

        return ($checkCategory instanceof Service);
    }

    public function getUserPermissions(User $user, Service $service)
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
        return $providerUser->permissions()->get();
    }

    public function getPermissionsListByUser(User $user, string $sort, string $order, ?int $count) {

        return null;
    }

    public function deleteUserPermissions(User $user, Provider $provider)
    {
        return null;
    }
}
