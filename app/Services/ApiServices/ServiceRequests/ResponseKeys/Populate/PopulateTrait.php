<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate;

trait PopulateTrait
{

    protected array $reservedKeys = [];
    protected ?array $data = [];
    protected bool $overwrite = false;

    public function setReservedKeys(array $reservedKeys): void
    {
        $this->reservedKeys = $reservedKeys;
    }

    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }
}
