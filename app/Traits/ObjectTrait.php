<?php

namespace App\Traits;

trait ObjectTrait
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
