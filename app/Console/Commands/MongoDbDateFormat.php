<?php

namespace App\Console\Commands;

use App\Helpers\Tools\DateHelpers;
use App\Http\Requests\Admin\User\CreateUserRequest;
use App\Models\Role;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Sr;
use App\Repositories\MongoDB\MongoDBRepository;
use App\Repositories\SrRepository;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\User\RoleService;
use App\Services\User\UserAdminService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Console\Command\Command as CommandAlias;

class MongoDbDateFormat extends Command
{
    private SrResponseKeyService $srResponseKeyService;
    private SrRepository $srRepository;
    private MongoDBRepository $mongoDBRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongodb:format-dates {--sr_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Format dates in mongodb';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        MongoDBRepository $mongoDBRepository,
        SrResponseKeyService $srResponseKeyService,
        SrRepository $srRepository
    )
    {
        $this->mongoDBRepository = $mongoDBRepository;
        $this->srResponseKeyService = $srResponseKeyService;
        $this->srRepository = $srRepository;

        $srId = $this->option('sr_id');
        if (empty($srId)){
            $srs = $this->srRepository->findAll();
        } else {
            $this->srRepository->addWhere(
                'id',
                array_map('intval', array_map('trim', explode(',', $srId))),
                'in'
            );
            $srs = $this->srRepository->findMany();
        }

        $this->collectionIterator(
            $srs,
            function () {
                return [
                    ['key' =>'updated_at', 'format' => null],
                    ['key' =>'created_at', 'format' => null],
                ];
            }
        );
        $this->collectionIterator(
            $srs,
            function (Sr $sr) {
                $dateKeys = $this->getDateResponseKeys($sr);
                if (!empty($defaultData['date']) && !$dateKeys->where('name', 'date_published')->first()) {
                    $srResponseKey = $this->srResponseKeyService->getSrResponseKeyRepository()->findOneSrResponseKeysWithRelation(
                        $sr,
                        [],
                        ['name' => 'date_published']
                    );
                    if ($srResponseKey) {
                        $dateKeys->add($srResponseKey);
                    }
                }
                $data = [];
                foreach ($dateKeys as $dateKey) {
                    $data[] = ['key' => $dateKey->name, 'format' => $dateKey?->srResponseKey?->date_format];
                }
                return $data;
            }
        );
        return CommandAlias::SUCCESS;
    }

    private function collectionIterator(Collection $srs, \Closure $callback) {

        foreach ($srs as $sr) {
            $collectionName = $this->mongoDBRepository->getCollectionName($sr);
            if (!$collectionName) {
                $this->error('Invalid collection name');
                return CommandAlias::FAILURE;
            }
            $this->mongoDBRepository->setCollection($collectionName);

            $defaultData = $sr->default_data;
            $dateKeys = $callback($sr);
            foreach ($this->mongoDBRepository->findAll() as $document) {
                $updateData = [];
                foreach ($dateKeys as $dateKey) {
                    if (empty($document[$dateKey['key']])) {
                        continue;
                    }
                    $date = $document[$dateKey['key']];
                    $dateFormat = $dateKey['format'];
                    if ($date instanceof UTCDateTime) {
                        $newDate = Carbon::createFromTimestamp($date);
                        if (!$newDate->greaterThan(Carbon::now())) {
                            continue;
                        }
                        $newDate = $document['updated_at'];
                        $updateData[$dateKey['key']] = $newDate;
                    } else {
                        $newDate = DateHelpers::parseDateString($date, $dateFormat);
                        if (!$newDate) {
                            $type = gettype($date);
                            $this->error("Error parsing date: {$date} | type: {$type} | collection: {$collectionName} | dateKey: {$dateKey['key']}");
                            continue;
                        }
                        $updateData[$dateKey['key']] = new UTCDateTime($newDate);
                    }
                }

                if (count($updateData) && !$this->mongoDBRepository->update($document['_id'], $updateData)) {
                    $this->error(
                        sprintf(
                            "Error updating document collection: %s | id: %s | data: %s",
                            $collectionName,
                            $document['_id'],
                            json_encode($updateData, JSON_PRETTY_PRINT)
                        )
                    );
                }
            }
        }
    }
    private function getDateResponseKeys(Sr $sr): Collection
    {
        $srResponseKeys = $this->srResponseKeyService->findResponseKeysForOperationBySr($sr);
        return $srResponseKeys->filter(function ($srResponseKey) {
            return (
                str_contains($srResponseKey->name, 'date') &&
                (
                    !empty($srResponseKey?->srResponseKey?->value) &&
                    DateHelpers::isValidDateString($srResponseKey->srResponseKey->value)
                )
            );
        });
    }
}
