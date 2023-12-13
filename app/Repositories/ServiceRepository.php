<?php

namespace App\Repositories;

use App\Models\Service;

class ServiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Service::class);
    }
    public function findByQuery($query)
    {
        $this->addWhere("label", "LIKE", "%$query%");
        $this->addWhere("name", "LIKE", "%$query%", "OR");
        return $this->findMany();
    }
}
