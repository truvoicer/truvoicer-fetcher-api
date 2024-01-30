<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\ProviderRateLimit;

class ProviderRateLimitRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(ProviderRateLimit::class);
    }

    public function getModel(): ProviderRateLimit
    {
        return parent::getModel();
    }

    public function buildData(array $data)
    {
        $newData = [];
        foreach (ProviderRateLimit::FIELDS as $key) {
            if (array_key_exists($key, $data)) {
                $newData[$key] = $data[$key];
            }
        }
        return $newData;
    }

    public function findRateLimitByProvider(Provider $provider)
    {
        return $provider->providerRateLimit()->first();
    }

    public function createProviderRateLimit(Provider $provider, array $data = []): bool
    {
        $create = $provider->providerRateLimit()->create($this->buildData($data));
        if (!$create->exists) {
            return false;
        }
        $this->setModel($create);
        return true;
    }
    public function saveProviderRateLimit(ProviderRateLimit $providerRateLimit, array $data = []): bool
    {
        $this->setModel($providerRateLimit);
        return $providerRateLimit->update($this->buildData($data));
    }

    public function deleteProviderRateLimit(ProviderRateLimit $providerRateLimit): bool
    {
        $this->setModel($providerRateLimit);
        return $providerRateLimit->delete();
    }
}
