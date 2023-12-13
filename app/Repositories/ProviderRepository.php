<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\User;
use App\Models\UserProvider;

class ProviderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Provider::class);
    }

    public function getAllProvidersArray() {
        return $this->findAll();
    }

    public function findByQuery($query)
    {
        return $this->findByLabelOrName($query);
    }

    /**
     * @param int $providerId
     * @return \Illuminate\Database\Eloquent\Model Returns an array of Provider objects
     */
    public function getProviderById(int $providerId)
    {
        return $this->findById($providerId);
    }

    public function findByParams(string $sort,  string $order, ?int $count)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }

    public function createProvider(User $user, array $providerData, array $permissions = [])
    {
        $insert = $this->insert($providerData);
        if (!$insert) {
            return false;
        }
        $userProviderRepo = new UserProviderRepository();
        return $userProviderRepo->createUserProvider($user, $this->model, $permissions);
    }

    public function updateProvider(Provider $provider)
    {
        $this->setModel($provider);
        return $this->save();
    }

    public function createProviderProperty(Provider $provider, Property $property, string $propertyValue) {
        $this->_em = $this->repositoryHelpers->getEntityManager($this->_em);
        try {
            $providerProp = new ProviderProperty();
            $providerProp->setProvider($provider);
            $providerProp->setProperty($property);
            $providerProp->setPropertyValue($propertyValue);
            $provider->addProviderProperty($providerProp);
            $this->getEntityManager()->persist($providerProp);
            $this->getEntityManager()->flush();
            return $providerProp;
        } catch (\Exception $exception) {
            return [
                "status" => "error",
                "message" => $exception->getMessage()
            ];
        }
    }

    public function getProviderProperty(Provider $provider, Property $property) {
        $em = $this->getEntityManager();
        $providerPropertyRepo = $em->getRepository(ProviderProperty::class);
        return $providerPropertyRepo->findOneBy(["provider" => $provider, "property" => $property]);
    }

    public function getProviderPropsByProviderId(int $providerId) {
        $em = $this->getEntityManager();
        $provider = $this->findOneBy(["id" => $providerId]);
        return $em->createQuery("SELECT   provprop FROM App\Entity\Property prop
                                   JOIN App\Entity\Provider provider
                                   JOIN App\Entity\ProviderProperty provprop
                                   WHERE provprop.provider = :provider")
            ->setParameter('provider', $provider)
            ->getResult();
    }

    public function deleteProvider(Provider $provider) {
        $entityManager = $this->getEntityManager();
        $getProvider = $this->findOneBy(["id" => $provider->getId()]);
        if ($getProvider != null) {
            $entityManager->remove($getProvider);
            $entityManager->flush();
            return true;
        }
        return false;
    }
    public function deleteProviderPropsByProvider(Provider $provider) {
        $em = $this->getEntityManager();
        return $em->createQuery("DELETE FROM App\Entity\ProviderProperty provprop WHERE provprop.provider = :provider")
            ->setParameter("provider", $provider)->execute();
    }
    public function deleteProviderCategories(Provider $provider) {
        $em = $this->getEntityManager();
        return $em->createQuery("DELETE FROM App\Entity\ProviderProperty provprop WHERE provprop.provider = :provider")
            ->setParameter("provider", $provider)->execute();
    }

}
