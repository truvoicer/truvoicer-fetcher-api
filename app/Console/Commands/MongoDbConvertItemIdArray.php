<?php

namespace App\Console\Commands;

use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBRepository;
use Truvoicer\TfDbReadCore\Repositories\SrRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Command\Command as CommandAlias;

class MongoDbConvertItemIdArray extends Command
{
    private SrRepository $srRepository;
    private MongoDBRepository $mongoDBRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongodb:convert-item-id-array {--sr_id=}';

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
        SrRepository $srRepository
    ) {
        $this->mongoDBRepository = $mongoDBRepository;
        $this->srRepository = $srRepository;

        $srId = $this->option('sr_id');
        if (empty($srId)) {
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
        );
        return CommandAlias::SUCCESS;
    }

    private function collectionIterator(Collection $srs)
    {

        foreach ($srs as $sr) {
            $collectionName = $this->mongoDBRepository->getCollectionName($sr);
            if (!$collectionName) {
                $this->error('Invalid collection name');
                return CommandAlias::FAILURE;
            }
            $this->mongoDBRepository->setCollection($collectionName);

            $limit = 100;
            $offset = 0;
            $finished = false;
            $results = [];
            while (!$finished) {
                $results = $this->mongoDBRepository->setLimit($limit)->setOffset($offset)->findMany();
                if (count($results) < $limit) {
                    $finished = true;
                }
                $offset += $limit;

                foreach ($results as $document) {
                    if (empty($document['item_id'])) {
                        continue;
                    }
                    if (!is_array($document['item_id'])) {
                        continue;
                    }
                    if (empty($document['item_id'][0]['data'])) {
                        continue;
                    }
                    $updateData = [
                        'item_id' => $document['item_id'][0]['data']
                    ];

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
    }
}
