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

    public function setPagination(bool $paginate = false): void
    {
        $this->paginate = $paginate;
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

}
