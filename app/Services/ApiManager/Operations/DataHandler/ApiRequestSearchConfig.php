<?php
namespace App\Services\ApiManager\Operations\DataHandler;

class ApiRequestSearchConfig
{
    private array $providers = [];
    private ?string $itemId = null;

    public function setProviders(array $providers): void
    {
        $this->providers = $providers;
    }
    public function getProviders(): array
    {
        return $this->providers;
    }
    public function setItemId(string $itemId): void
    {
        $this->itemId = $itemId;
    }
    public function getItemId(): ?string
    {
        return $this->itemId;
    }
}
