<?php

namespace App\Repositories;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrSchedule;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function fetchSrsByScheduleInterval(Provider $provider, string $interval)
    {
        $today = now()->toDateString();
        $serviceRequests = $provider->sr()
            ->whereHas('srSchedule', function (HasOne $query) use ($interval, $today) {
                $query->where($interval, true)
                    ->where('disabled', false)
                    ->where('locked', false)
                    ->whereDate('end_date', '<=', $today)
                    ->whereDate('start_date', '>=', $today);
            })
            ->get();
        return $serviceRequests;
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
