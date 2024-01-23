<?php

namespace App\Repositories;

use App\Models\Sr;
use App\Models\SrSchedule;

class SrScheduleRepository extends BaseRepository
{

    public function __construct()
    {
        parent::__construct(SrSchedule::class);
    }

    public function getModel(): SrSchedule
    {
        return parent::getModel();
    }

    public function findBySr(Sr $serviceRequest)
    {
        return $serviceRequest->srSchedule()->first();
    }

    public function buildSaveData(array $data)
    {
        $saveData = [];
        foreach (SrSchedule::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = $data[$field];
            }
        }
        return $saveData;
    }

    public function createSrSchedule(Sr $serviceRequest, array $data)
    {
        $this->setModel(
            $serviceRequest->srSchedule()->create($this->buildSaveData($data))
        );
        return $this->getModel()->exists;
    }
    public function saveSrSchedule(array $data)
    {
        return $this->save($data);
    }
    public function deleteSrSchedule(SrSchedule $srSchedule)
    {
        $this->setModel($srSchedule);
        return $this->delete();
    }
}
