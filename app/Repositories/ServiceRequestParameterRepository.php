<?php

namespace App\Repositories;

use App\Models\ServiceRequestParameter;

class ServiceRequestParameterRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestParameter::class);
    }
    public function findByParams(ServiceRequest $serviceRequest, string $sort, string $order, int $count)
    {
        $query = $this->createQueryBuilder('p')
            ->addOrderBy('p.' . $sort, $order);
        if ($count !== null && $count > 0) {
            $query->setMaxResults($count);
        }
        $query->where("p.service_request = :serviceRequest")
            ->setParameter("serviceRequest", $serviceRequest);
        return $query->getQuery()
            ->getResult();
    }

    public function getRequestParametersByRequestName(Provider $provider, string $serviceRequestName = null)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT srp FROM App\Entity\ServiceRequest sr
                            JOIN App\Entity\ServiceRequestParameter srp
                            WHERE sr.provider = :provider
                            AND  srp.service_request = sr
                            AND sr.service_request_name = :serviceRequestName")
            ->setParameter("provider", $provider)
            ->setParameter("serviceRequestName", $serviceRequestName)
            ->getResult();
    }

    public function save(ServiceRequestParameter $serviceRequestParameter)
    {
        $this->_em = $this->repositoryHelpers->getEntityManager($this->_em);
        try {
            $this->getEntityManager()->persist($serviceRequestParameter);
            $this->getEntityManager()->flush();
            return $serviceRequestParameter;
        } catch (UniqueConstraintViolationException $exception) {
            return [
                "status" => "error",
                "message" => sprintf("Duplicate entry for service request parameter: (%s)", $serviceRequestParameter->getParameterName())
            ];
        } catch (\Exception $exception) {
            return [
                "status" => "error",
                "message" => $exception->getMessage()
            ];
        }
    }

    public function delete(ServiceRequestParameter $service)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($service);
        $entityManager->flush();
        return $service;
    }
}
