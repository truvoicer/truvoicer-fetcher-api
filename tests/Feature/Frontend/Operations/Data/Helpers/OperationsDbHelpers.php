<?php

namespace Tests\Feature\Frontend\Operations\Data\Helpers;

use Truvoicer\TruFetcherGet\Models\Category;
use App\Models\Mongo\Entity;
use App\Models\Mongo\EntityList;
use Truvoicer\TruFetcherGet\Models\Property;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Models\S;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Repositories\MongoDB\MongoDBRepository;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\DB;

class OperationsDbHelpers
{

    private MongoDBRepository $mongoDbRepository;

    public function __construct()
    {
        $this->mongoDbRepository = app(MongoDBRepository::class);

        $databaseName = DB::connection('mongodb')->getDatabaseName();
        $this->mongoDbRepository->getMongoDBQuery()
            ->getConnection()
            ->getMongoClient()
            ->dropDatabase($databaseName);
    }

    static public function instance()
    {
        return app(self::class);
    }

    public function dataInit(
        Provider $provider,
        array $srResponseKeys,
        S $s,
        Category $category,
        array $properties,
        array $srConfigs,
        array $responseData,
        ?int $entityCount = 1,
    ): void {
        $provider->each(function (Provider $provider) use (
            $srResponseKeys,
            $s,
            $category,
            $properties,
            $srConfigs,
            $responseData,
            $entityCount,
        ) {

            foreach ($properties as $property) {
                $findProperty = Property::where('name', $property['name'])->first();
                unset($property['name']);
                $provider->properties()->attach(
                    $findProperty->id,
                    $property
                );
            }

            $provider->sr->each(function (Sr $sr) use (
                $provider,
                $srResponseKeys,
                $s,
                $category,
                $srConfigs,
                $responseData,
                $entityCount,
            ) {
                foreach ($srConfigs as $srConfig) {
                    $findProperty = Property::where('name', $srConfig['name'])->first();
                    $data = ['property_id' => $findProperty->id];
                    if (array_key_exists('value', $srConfig)) {
                        $data['value'] = $srConfig['value'];
                    } elseif (array_key_exists('big_text_value', $srConfig)) {
                        $data['big_text_value'] = $srConfig['big_text_value'];
                    } elseif (array_key_exists('array_value', $srConfig)) {
                        $data['array_value'] = $srConfig['array_value'];
                    }
                    $sr->srConfig()->create($data);
                }

                foreach ($srResponseKeys as $srResponseKey) {
                    $s = $sr->s;

                    $sResponseKey = $s->sResponseKeys()
                        ->where(
                            's_id',
                            $s->id
                        )
                        ->where(
                            'name',
                            $srResponseKey['name']
                        )
                        ->first();
                    if (!$sResponseKey) {
                        $sResponseKey = $s->sResponseKeys()->create([
                            's_id' => $s->id,
                            'name' => $srResponseKey['name']
                        ]);
                    }

                    $srResponseKeyData = [
                        's_response_key_id' => $sResponseKey->id,
                    ];

                    if (array_key_exists('value', $srResponseKey)) {
                        $srResponseKeyData['value'] = $srResponseKey['value'];
                    }
                    if (array_key_exists('show_in_response', $srResponseKey)) {
                        $srResponseKeyData['show_in_response'] = $srResponseKey['show_in_response'];
                    }
                    if (array_key_exists('list_item', $srResponseKey)) {
                        $srResponseKeyData['list_item'] = $srResponseKey['list_item'];
                    }
                    if (array_key_exists('custom_value', $srResponseKey)) {
                        $srResponseKeyData['custom_value'] = $srResponseKey['custom_value'];
                    }
                    if (array_key_exists('search_priority', $srResponseKey)) {
                        $srResponseKeyData['search_priority'] = $srResponseKey['search_priority'];
                    }
                    if (array_key_exists('searchable', $srResponseKey)) {
                        $srResponseKeyData['searchable'] = $srResponseKey['searchable'];
                    }
                    if (array_key_exists('is_date', $srResponseKey)) {
                        $srResponseKeyData['is_date'] = $srResponseKey['is_date'];
                    }
                    if (array_key_exists('date_format', $srResponseKey)) {
                        $srResponseKeyData['date_format'] = $srResponseKey['date_format'];
                    }
                    if (array_key_exists('append_extra_data_value', $srResponseKey)) {
                        $srResponseKeyData['append_extra_data_value'] = $srResponseKey['append_extra_data_value'];
                    }
                    if (array_key_exists('prepend_extra_data_value', $srResponseKey)) {
                        $srResponseKeyData['prepend_extra_data_value'] = $srResponseKey['prepend_extra_data_value'];
                    }
                    if (array_key_exists('array_keys', $srResponseKey)) {
                        $srResponseKeyData['array_keys'] = $srResponseKey['array_keys'];
                    }
                    $srRKey = $sr->srResponseKey()
                        ->create($srResponseKeyData);
                }
                $entityData = [];
                $entityData['serviceRequest'] = $sr->name;
                $entityData['request_type'] = 'response_keys';
                $entityData['response_format'] = 'json';
                $entityData['content_type'] = 'json';
                $entityData['provider'] = $provider->name;
                $entityData['service'] = ['name' => $s->name];
                $entityData['request_category'] = $category->name;

                $collectionName = $this->mongoDbRepository->getCollectionName(
                    $sr
                );
                $mongoDBQuery = $this->mongoDbRepository->getMongoDBQuery();
                $mongoDBQuery->setCollection($collectionName);
                if ($entityCount) {
                    $entityListFactory = EntityList::factory()
                        ->count($entityCount);
                    if (is_array($responseData) && count($responseData)) {
                        $entityListFactory = $entityListFactory->state(
                            new Sequence(
                                function (Sequence $sequence) use (
                                    $entityData,
                                    $responseData
                                ) {
                                    $extractItem = $responseData[$sequence->index];
                                    unset($extractItem['id']);
                                    return array_merge(
                                        $entityData,
                                        $extractItem
                                    );
                                }
                            )
                        );
                    }
                    $entityListFactory->make()->each(function (EntityList $entityList) use ($mongoDBQuery) {
                        $insertData = $entityList->toArray();
                        $mongoDBQuery->insert($insertData);
                    });
                }
            });
        });
    }
}
