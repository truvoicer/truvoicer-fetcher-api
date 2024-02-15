<?php

namespace App\Traits\Database;

use App\Helpers\Tools\ClassHelpers;
use App\Models\User;
use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait PaginationTrait
{
    public bool $paginate = false;
    public int $perPage = 10;
    public int $page = 1;
    public int $total = 0;

    public function setPagination(bool $paginate = false, int $perPage = 10, int $page = 1): void
    {
        $this->paginate = $paginate;
        $this->perPage = $perPage;
        $this->page = $page;
    }
}
