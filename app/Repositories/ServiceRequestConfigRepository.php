<?php

namespace App\Repositories;

use App\Models\ServiceRequestConfig;

class ServiceRequestConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequestConfig::class);
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

    public function getRequestConfigByName(Provider $provider, ServiceRequest $serviceRequest, string $configItemName)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT src FROM App\Entity\ServiceRequest sr
                            JOIN App\Entity\ServiceRequestConfig src
                            WHERE sr.provider = :provider
                            AND  src.service_request = sr
                            AND sr = :serviceRequest
                            AND src.item_name =:configItemName")
            ->setParameter("provider", $provider)
            ->setParameter("serviceRequest", $serviceRequest)
            ->setParameter('configItemName', $configItemName)
            ->getOneOrNullResult();
    }

    public function save(ServiceRequestConfig $serviceRequestConfig)
    {
        $this->_em = $this->repositoryHelpers->getEntityManager($this->_em);
        try {
            $this->getEntityManager()->persist($serviceRequestConfig);
            $this->getEntityManager()->flush();
            return $serviceRequestConfig;
        } catch (UniqueConstraintViolationException $exception) {
            return [
                "status" => "error",
                "message" => sprintf("Duplicate entry for service request config: (%s)", $serviceRequestConfig->getItemName())
            ];
        } catch (\Exception $exception) {
            return [
                "status" => "error",
                "message" => $exception->getMessage()
            ];
        }
    }

    public function delete(ServiceRequestConfig $service)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($service);
        $entityManager->flush();
        return $service;
    }
}
