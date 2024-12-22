<?php

namespace App\Console\Commands;

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
use Symfony\Component\Console\Command\Command as CommandAlias;

class MongoDbDateFormat extends Command
{
    private SrResponseKeyService $srResponseKeyService;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongodb:format-dates';

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
        $this->srResponseKeyService = $srResponseKeyService;

        $srs = $srRepository->findAll();
        foreach ($srs as $sr) {
            if (!$sr->provider()->where('name', 'reed')->exists()) {
                continue;
            }
            $collectionName = $mongoDBRepository->getCollectionName($sr);
            if (!$collectionName) {
                $this->error('Invalid collection name');
                return CommandAlias::FAILURE;
            }
            $mongoDBRepository->setCollection($collectionName);

            $defaultData = $sr->default_data;
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

            foreach ($mongoDBRepository->findAll() as $document) {
                foreach ($dateKeys as $dateKey) {
                    if (empty($document[$dateKey->name])) {
                        continue;
                    }
                    $date = $document[$dateKey->name];
                    $isDate = $dateKey->srResponseKey?->is_date;
                    $dateFormat = $dateKey->srResponseKey?->date_format;

                    $newDate = null;
                    try {
                        if ($dateFormat) {
                            $newDate = Carbon::createFromFormat($dateFormat, $date)->toISOString();
                        } else {
                            $newDate = Carbon::parse($date)->toISOString();
                        }
                    } catch (\Exception $e) {
                        if (str_contains($date, '/')) {
                            $newDate = Carbon::parse(str_replace('/', '-', $date))->toISOString();
                        }
                        dd($dateFormat, $date, $newDate);
                    }
                }
//                $mongoDBRepository->update($document);
            }
        }
        return CommandAlias::SUCCESS;
    }
    private function getDateResponseKeys(Sr $sr): Collection
    {
        $srResponseKeys = $this->srResponseKeyService->findResponseKeysForOperationBySr($sr);
        return $srResponseKeys->filter(function ($srResponseKey) {
            return str_contains($srResponseKey->name, 'date');
        });
    }
}
