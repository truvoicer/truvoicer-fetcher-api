<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ServiceFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;
//
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * @throws \Exception
     */
    public function getService(string $serviceId) {
        try {
            $service = $this->container->get(
                $serviceId,
                ContainerInterface::NULL_ON_INVALID_REFERENCE
            );
            if (!$service) {
                throw new BadRequestHttpException(sprintf("Invalid service [%s]", $serviceId));
            }
            return $service;
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        return false;
    }

}
