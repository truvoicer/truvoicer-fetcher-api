<?php

namespace App\Services;

use Truvoicer\TruFetcherGet\Enums\Entity\EntityType;
use Truvoicer\TruFetcherGet\Enums\Import\EntityLockStatus;
use Truvoicer\TruFetcherGet\Http\Resources\ProviderMinimalCollection;
use Truvoicer\TruFetcherGet\Http\Resources\Service\ServiceRequest\SrTreeViewCollection;
use Truvoicer\TruFetcherGet\Models\EntityLock;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\ProviderProperty;
use Truvoicer\TruFetcherGet\Models\ProviderPropertyEntity;
use Truvoicer\TruFetcherGet\Models\SrConfig;
use Truvoicer\TruFetcherGet\Models\SrConfigEntity;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\ProviderRepository;
use Truvoicer\TruFetcherGet\Repositories\SrRepository;

class EntityService extends BaseService
{

    private SrRepository $srRepository;
    private ProviderRepository $providerRepository;

    public function __construct()
    {
        parent::__construct();
        $this->srRepository = new SrRepository();
        $this->providerRepository = new ProviderRepository();
    }

    public function getEntityList(User $user, string $entity, ?array $ids): SrTreeViewCollection|ProviderMinimalCollection|null
    {
        switch ($entity) {
            case EntityType::ENTITY_SR->value:
                $this->srRepository->setPagination(false);
                return new SrTreeViewCollection(
                    $this->srRepository->getUserServiceRequestByProviderIds(
                        $user,
                        $ids
                    )
                );
            case EntityType::ENTITY_PROVIDER->value:
                $this->providerRepository->setPagination(false);
                return new ProviderMinimalCollection(
                    $this->providerRepository->findUserProviders(
                        $user
                    )
                );
        }
        return null;
    }
    public function getEntityListByEntityIds(
        User $user,
        string $entity,
        ?array $ids
    ) {
        switch ($entity) {
            case EntityType::ENTITY_SR->value:
                return  $this->srRepository->getUserServiceRequestByIds($user, $ids);
            case EntityType::ENTITY_PROVIDER->value:
                return $this->providerRepository
                    ->setQuery(
                        Provider::whereIn('id', $ids)
                    )
                    ->findUserProviders(
                        $user
                    );
        }
        return null;
    }

    public function findEntityId(
        User $user,
        string $entity,
        int $id
    ) {
        switch ($entity) {
            case EntityType::ENTITY_SR->value:
                return $this->srRepository->getUserServiceRequestByIds($user, [$id])->first();
            case EntityType::ENTITY_PROVIDER->value:
                return $this->providerRepository
                    ->setQuery(
                        Provider::where('id', $id)
                    )
                    ->findUserProviders(
                        $user
                    )->first();
        }
        return null;
    }

    public function removeMissingProviderPropertyEntities(
        EntityType $entityType,
        array $ids
    ): void {
        $className = $entityType->className();
        $instance = new $className();
        ProviderPropertyEntity::whereDoesntHaveMorph(
            'entityable',
            $entityType->className(),
            function ($query) use ($ids, $instance) {
                $query->whereIn(
                    $instance->getTable().'.id',
                    $ids
                );
            }
        )->delete();
    }
    public function removeMissingSrConfigEntities(
        EntityType $entityType,
        array $ids
    ): void {
        $className = $entityType->className();
        $instance = new $className();
        SrConfigEntity::whereDoesntHaveMorph(
            'entityable',
            $entityType->className(),
            function ($query) use ($ids, $instance) {
                $query->whereIn(
                    $instance->getTable().'.id',
                    $ids
                );
            }
        )->delete();
    }

    public function syncProviderPropertyEntities(
        User $user,
        ProviderProperty $providerProperty,
        EntityType $entityType,
        array $ids
    ): void {
        $this->removeMissingProviderPropertyEntities(
            $entityType,
            $ids
        );
        $entities = $this->getEntityListByEntityIds(
            $user,
            $entityType->value,
            $ids
        );
        foreach ($entities as $entity) {
            $entity->providerPropertyEntities()->updateOrCreate([
                'provider_property_id' => $providerProperty->id
            ]);
        }
    }
    public function syncSrConfigEntities(
        User $user,
        SrConfig $srConfig,
        EntityType $entityType,
        array $ids
    ): void {
        $this->removeMissingSrConfigEntities(
            $entityType,
            $ids
        );
        $entities = $this->getEntityListByEntityIds(
            $user,
            $entityType->value,
            $ids
        );
        foreach ($entities as $entity) {
            $entity->srConfigEntities()->updateOrCreate([
                'sr_config_id' => $srConfig->id
            ]);
        }
    }

    public function lockEntity(User $user, int $id, string $class)
    {
        $lockProvider = EntityLock::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'entity_id' => $id,
                'entity_type' => $class
            ],
            [
                'entity_id' => $id,
                'entity_type' => $class,
                'user_id' => $user->id,
                'status' => EntityLockStatus::LOCKED,
                'locked_at' => now(),
                'unlocked_at' => null
            ]
        );
        return $lockProvider->status === EntityLockStatus::LOCKED;
    }

    public function unlockEntity(User $user, int $id, string $class)
    {
        EntityLock::query()
            ->where('user_id', $user->id)
            ->where('entity_id', $id)
            ->where('entity_type', $class)
            ->delete();
        return true;
    }

    public static function getInstance(): EntityService
    {
        return new EntityService();
    }
}
