<?php

namespace App\Repositories\MongoDB;

class BaseRepository
{
    public function __construct(
        protected MongoDBQuery $mongoDBQuery,
        protected MongoDBRaw   $mongoDBRaw,
    ) {}

    public function getMongoDBQuery(): MongoDBQuery
    {
        return $this->mongoDBQuery;
    }

    public function getMongoDBRaw(): MongoDBRaw
    {
        return $this->mongoDBRaw;
    }
}
