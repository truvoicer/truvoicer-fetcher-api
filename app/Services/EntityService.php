<?php

namespace App\Services;

use App\Http\Resources\Service\ServiceRequest\SrTreeViewCollection;
use App\Models\User;
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

    public function __construct(
        private SrService $srService
    )
    {
        parent::__construct();
    }

    public function getEntityList(User $user, string $entity, ?array $ids): ?SrTreeViewCollection
    {
        switch ($entity) {
            case self::ENTITY_SR:
                return new SrTreeViewCollection(
                    $this->srService->getUserServiceRequestByProviderIds($user, $ids)
                );
        }
        return null;
    }

}
