<?php

namespace App\Services;

use App\Http\Resources\Service\ServiceRequest\SrTreeViewCollection;
use App\Models\User;
use App\Repositories\SrRepository;
use App\Services\ApiServices\ServiceRequests\SrService;

class EntityService extends BaseService
{
    public const ENTITY_SR = 'service_requests';
    public const ENTITY_PROVIDER = 'provider';
    public const ENTITY_USER = 'user';
    public const ENTITY_SERVICE = 'service';
    public const ENTITY_PROPERTY = 'property';
    public const ENTITY_SR_CONFIG = 'sr_config';
    public const ENTITY_SR_PARAMETER = 'sr_parameter';
    public const ENTITIES = [
        self::ENTITY_SR,
        self::ENTITY_PROVIDER,
        self::ENTITY_USER,
        self::ENTITY_SERVICE,
        self::ENTITY_PROPERTY,
        self::ENTITY_SR_CONFIG,
        self::ENTITY_SR_PARAMETER,
    ];

    private SrRepository $srRepository;

    public function __construct(
    )
    {
        parent::__construct();
        $this->srRepository = new SrRepository();
    }

    public function getEntityList(User $user, string $entity, ?array $ids): ?SrTreeViewCollection
    {
        switch ($entity) {
            case self::ENTITY_SR:
                $this->srRepository->setPagination(false);
                return new SrTreeViewCollection(
                    $this->srRepository->getUserServiceRequestByProviderIds(
                        $user, $ids
                    )
                );
        }
        return null;
    }
    public function getEntityListByEntityIds(User $user, string $entity, ?array $ids)
    {
        switch ($entity) {
            case self::ENTITY_SR:
                return  $this->srRepository->getUserServiceRequestByIds($user, $ids);
        }
        return null;
    }

    public static function getInstance(): EntityService
    {
        return new EntityService();
    }

}
