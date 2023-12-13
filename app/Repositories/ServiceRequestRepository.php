<?php

namespace App\Repositories;

use App\Models\ServiceRequest;

class ServiceRequestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ServiceRequest::class);
    }

    public function findByQuery($query)
    {
        $query = $this->createQueryBuilder('sr')
            ->where("sr.service_request_label LIKE :query")
            ->orWhere("sr.service_request_name LIKE :query")
            ->setParameter("query", "%" . $query . "%")
            ->getQuery()
            ->getResult();
        return $query;
    }

    public function getServiceRequestByProvider(Provider $provider, string $sort, string $order, int $count) {
        $query = $this->createQueryBuilder('p')
            ->addOrderBy('p.'.$sort, $order);
        if ($count !== null && $count > 0) {
            $query->setMaxResults($count);
        }
        $query->where("p.provider = :provider")
            ->setParameter("provider", $provider);
        return $query->getQuery()
            ->getResult()
            ;
    }

    public function getRequestByName(Provider $provider, string $serviceRequestName) {
        return $this->getEntityManager()
            ->createQuery("SELECT sr from App\Entity\ServiceRequest sr
                            JOIN App\Entity\Service s
                            WHERE sr.service = s
                            AND sr.provider = :provider
                            AND sr.service_request_name = :serviceRequestName")
            ->setParameter("provider", $provider)
            ->setParameter("serviceRequestName", $serviceRequestName)
            ->getOneOrNullResult();
    }

    public function save(ServiceRequest $serviceRequest)
    {
        $this->_em = $this->repositoryHelpers->getEntityManager($this->_em);
        try {
            $this->getEntityManager()->persist($serviceRequest);
            $this->getEntityManager()->flush();
            return $serviceRequest;
        } catch (UniqueConstraintViolationException $exception) {
            return [
                "status" => "error",
                "message" => sprintf("Duplicate entry for service request: (%s)", $serviceRequest->getServiceRequestName())
            ];
        } catch (\Exception $exception) {
            return [
                "status" => "error",
                "message" => $exception->getMessage()
            ];
        }
    }

    public function duplicateServiceRequest(ServiceRequest $serviceRequest, array $data)
    {
        $requestResponseKeysRepo = $this->getEntityManager()->getRepository(ServiceRequestResponseKey::class);

        $newServiceRequest = new ServiceRequest();
        $newServiceRequest->setServiceRequestName($data['item_name']);
        $newServiceRequest->setServiceRequestLabel($data['item_label']);
        if (!empty($data['item_pagination_type'])) {
            $newServiceRequest->setPaginationType($data['item_pagination_type']);
        }
        $newServiceRequest->setService($serviceRequest->getService());
        $newServiceRequest->setCategory($serviceRequest->getCategory());
        $newServiceRequest->setProvider($serviceRequest->getProvider());

        $requestResponseKeysRepo->duplicateRequestResponseKeys($serviceRequest, $newServiceRequest);

        $requestConfig = $serviceRequest->getServiceRequestConfigs();
        foreach ($requestConfig as $item) {
            $serviceRequestConfig = new ServiceRequestConfig();
            $serviceRequestConfig->setItemName($item->getItemName());
            $serviceRequestConfig->setItemValue($item->getItemValue());
            $serviceRequestConfig->setValueType($item->getValueType());
            $serviceRequestConfig->setServiceRequest($newServiceRequest);
            $newServiceRequest->addServiceRequestConfig($serviceRequestConfig);
            $this->getEntityManager()->persist($serviceRequestConfig);
        }

        $requestParams = $serviceRequest->getServiceRequestParameters();
        foreach ($requestParams as $item) {
            $serviceRequestParams = new ServiceRequestParameter();
            $serviceRequestParams->setParameterName($item->getParameterName());
            $serviceRequestParams->setParameterValue($item->getParameterValue());
            $serviceRequestParams->setServiceRequest($newServiceRequest);
            $newServiceRequest->addServiceRequestParameter($serviceRequestParams);
            $this->getEntityManager()->persist($serviceRequestParams);
        }
        $this->getEntityManager()->persist($newServiceRequest);
        $this->getEntityManager()->flush();
        return $serviceRequest;
    }

    public function delete(ServiceRequest $service) {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($service);
        $entityManager->flush();
        return $service;
    }
}
