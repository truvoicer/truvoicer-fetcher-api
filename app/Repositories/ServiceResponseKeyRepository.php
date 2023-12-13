<?php

namespace App\Repositories;

use App\Models\ServiceResponseKey;

class ServiceResponseKeyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceResponseKey::class);
    }


    public function getServiceResponseKeyByName(Service $service, string $responseKeyName)
    {
        foreach ($service->getServiceResponseKeys() as $responseKey) {
            if ($responseKey->getKeyName() === $responseKeyName) {
                return $responseKey;
            }
        }
        return null;
    }

    public function getResponseKeys(Provider $provider, ServiceRequest $serviceRequest)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT srk FROM App\Entity\ServiceRequest sr
                            JOIN App\Entity\ServiceResponseKey srk
                            JOIN App\Entity\ServiceRequestResponseKey srrk
                            WHERE sr.provider = :provider
                            AND  sr = :serviceRequest
                            AND sr.service = srk.service")
            ->setParameter("provider", $provider)
            ->setParameter("serviceRequest", $serviceRequest)
            ->getResult();
    }

    public function getRequestResponseKey(ServiceRequest $serviceRequest, ServiceResponseKey $responseKey) {
        $requestResponseKeyRepo = $this->getEntityManager()->getRepository(ServiceRequestResponseKey::class);
        return $requestResponseKeyRepo->findOneBy([
            "service_request" => $serviceRequest,
            "service_response_key" => $responseKey
        ]);
    }

    public function save(ServiceResponseKey $serviceResponseKey)
    {
        $this->_em = $this->repositoryHelpers->getEntityManager($this->_em);
        try {
            $this->getEntityManager()->persist($serviceResponseKey);
            $this->getEntityManager()->flush();
            return $serviceResponseKey;
        } catch (UniqueConstraintViolationException $exception) {
            return [
                "status" => "error",
                "message" => sprintf("Duplicate entry for service response key: (%s)", $serviceResponseKey->getKeyName())
            ];
        } catch (\Exception $exception) {
            return [
                "status" => "error",
                "message" => $exception->getMessage()
            ];
        }
    }

    public function delete(ServiceResponseKey $service) {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($service);
        $entityManager->flush();
        return $service;
    }
}
