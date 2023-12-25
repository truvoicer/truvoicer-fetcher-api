<?php

namespace App\Services;

use App\Models\User;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;

class BaseService
{
    use UserTrait, ErrorTrait;


}
