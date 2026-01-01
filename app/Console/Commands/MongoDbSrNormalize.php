<?php

namespace App\Console\Commands;

use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Repositories\MongoDB\MongoDBRepository;
use Truvoicer\TfDbReadCore\Repositories\SrRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Command\Command as CommandAlias;

class MongoDbSrNormalize extends Command
{
    private SrRepository $srRepository;
    private MongoDBRepository $mongoDBRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongodb:sr-normalize {--src_sr_id=} {--dest_sr_id=} {--fields=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize SR data in MongoDB';

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

        $srcSrId = $this->option('src_sr_id');
        $destSrId = $this->option('dest_sr_id');
        $fields = $this->option('fields');
        $fields = array_map('trim', explode(',', $fields));
        if (!count($fields)) {
            $this->error('Please provide at least one field to normalize.');
            return CommandAlias::FAILURE;
        }
        if (empty($srcSrId) || empty($destSrId) || empty($fields)) {
            $this->error('Please provide --src_sr_id, --dest_sr_id, and --fields options.');
            return CommandAlias::FAILURE;
        }

        $srcSr = $this->srRepository->findById($srcSrId);
        if (!$srcSr) {
            $this->error('Source SR not found.');
            return CommandAlias::FAILURE;
        }

        $destSr = $this->srRepository->findById($destSrId);
        if (!$destSr) {
            $this->error('Destination SR not found.');
            return CommandAlias::FAILURE;
        }

        $this->collectionIterator(
            $srcSr,
            $destSr,
            $fields
        );
        return CommandAlias::SUCCESS;
    }

    private function setCollectionBySr(Sr $sr): void
    {
        $collectionName = $this->mongoDBRepository->getCollectionName($sr);
        if (!$collectionName) {
            throw new \Exception('Could not determine collection name for SR: ' . $sr->name);
        }
        $this->mongoDBRepository->setCollection($collectionName);
    }

    private function collectionIterator(Sr $srcSr, Sr $destSr, array $fields)
    {
        $this->setCollectionBySr($srcSr);

        $limit = 100;
        $offset = 0;
        $finished = false;
        $results = [];
        while (!$finished) {
            $this->info('Processing documents from ' . $this->mongoDBRepository->getCollectionName($srcSr) . ' with offset ' . $offset);
            $results = $this->mongoDBRepository->setLimit($limit)->setOffset($offset)->findMany();
            $this->info('Found ' . count($results) . ' documents.');
            if (count($results) < $limit) {
                $finished = true;
            }
            $offset += $limit;

            foreach ($results as $document) {
                $match = array_filter($fields, function ($field) use ($document) {
                    return array_key_exists($field, $document);
                });
                if (count($match) !== count($fields)) {
                    continue;
                }

                $this->setCollectionBySr($destSr);
                foreach ($fields as $field) {
                    $this->mongoDBRepository->buildWhereData(
                        $field,
                        $document[$field],
                        '='
                    );
                }
                $existing = $this->mongoDBRepository->findOne();

                if ($existing) {
                    $this->info('Document exists in destination SR collection ' . $this->mongoDBRepository->getCollectionName($destSr) . ', skipping.');
                    continue;
                }

                $this->setCollectionBySr($srcSr);
                $delete = $this->mongoDBRepository->deleteBatch([$document['_id']]);
                if ($delete === 1) {
                    $this->info('Deleted document with _id: ' . $document['_id']);
                } else {
                    $this->error('Failed to delete document with _id: ' . $document['_id']);
                }
            }
        }
    }
}
