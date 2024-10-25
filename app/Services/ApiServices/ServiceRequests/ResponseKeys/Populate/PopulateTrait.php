<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate;

trait PopulateTrait
{
    protected ?array $data = [];
    protected bool $overwrite = false;

    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }
}
