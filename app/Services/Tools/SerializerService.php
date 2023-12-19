<?php

namespace App\Services\Tools;


class SerializerService
{

    public function entityToArray($entity, array $groups = ['main'])
    {
        return $entity;
    }

    public function entityArrayToArray($entityArray, array $groups = ['main'])
    {
        return $entityArray;
    }

    public function entityToXml($entity, array $groups)
    {
        return null;
    }

    public function xmlArrayToEntities(string $xmlContent, string $class)
    {
        return null;
    }

}
