<?php

namespace App\Repositories;

use App\Helpers\Tools\DateHelpers;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SrScheduleRepository extends BaseRepository
{
    private ProviderRepository $providerRepository;

    public function __construct()
    {
        parent::__construct(SrSchedule::class);
        $this->providerRepository = new ProviderRepository();
    }

    public function getModel(): SrSchedule
    {
        return parent::getModel();
    }

    public function findBySr(Sr $serviceRequest)
    {
        return $serviceRequest->srSchedule()->first();
    }

    public function fetchSrsByScheduleInterval(Provider $provider, string $interval, ?bool $executeImmediately = false)
    {
        $today = now()->toDateString();
        $serviceRequests = $provider->sr()
            ->whereHas('srSchedule', function (Builder $query) use ($interval, $today, $executeImmediately) {
                if (!$executeImmediately) {
                    $query->where($interval, '=', true)
                        ->whereNull('end_date')
                        ->orWhereDate('end_date', '<=', $today)
                        ->whereNull('start_date')
                        ->orWhereDate('start_date', '>=', $today);
                }
                $query->where('disabled', false)
                    ->where('locked', false);
            });
        return $this->getResults(
            $serviceRequests
        );
    }

    public function buildSaveData(array $data)
    {
        $saveData = [];
        foreach (SrSchedule::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $saveData[$field] = match ($field) {
                    'month' => (is_string($data[$field]))
                        ? DateHelpers::convertMonthToInteger($data[$field])
                        : $data[$field],
                    default => $data[$field],
                };
            }
            return $saveData;
        }
    }

    public function createSrSchedule(Sr $serviceRequest, array $data)
    {
        $this->setModel(
            $serviceRequest->srSchedule()->create($this->buildSaveData($data))
        );
        return $this->getModel()->exists;
    }

    public
    function saveSrSchedule(array $data)
    {
        return $this->save($data);
    }

    public
    function deleteSrSchedule(SrSchedule $srSchedule)
    {
        $this->setModel($srSchedule);
        return $this->delete();
    }
}
