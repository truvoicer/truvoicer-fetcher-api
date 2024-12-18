<?php

namespace App\Enums\Import;

enum EntityLockStatus : string
{
    case LOCKED = "locked";
    case UNLOCKED = "unlocked";
}
