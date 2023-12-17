<?php
namespace App\Services;

use Illuminate\Support\Facades\App;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ServiceFactory
{
    public function getService(string $serviceId) {
        try {
            $service = App::make($serviceId);
            if (!$service) {
                throw new BadRequestHttpException(sprintf("Invalid service [%s]", $serviceId));
            }
            return $service;
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

}
