<?php

namespace Tests\Resources\Data\ImportExport;

use App\Enums\Import\ImportAction;
use App\Models\Category;
use App\Models\Property;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SResponseKey;
use App\Models\SrParameter;
use App\Models\SrRateLimit;
use App\Models\SrResponseKey;
use App\Models\SrSchedule;

class DefaultData
{

    public static function fullData(): array
    {
        $categories = Category::factory(2)->create();
        $properties = Property::factory(2)->create();
        $services = S::factory(2)
            ->has(
                SResponseKey::factory(2)
            )
            ->create();
        $service = $services->first();

        $providers = Provider::factory(2)
            ->has(
                Sr::factory(2)
                    ->has(
                        SrConfig::factory()->withProperty($properties->first())
                    )
                    ->has(
                        SrParameter::factory(2)
                    )
                    ->has(
                        SrSchedule::factory()
                    )
                    ->has(
                        SrRateLimit::factory()
                    )
                    ->hasS($service)
            )
            ->create();
        $providers->each(function ($provider) use ($services) {
            $provider->srs->each(function ($sr) use ($services) {
                $service = $services->first();
                $sr->s()->associate($service)->save();
                SrResponseKey::factory()->create([
                    'sr_id' => $sr->id,
                    's_response_key_id' => $service->sResponseKey->first()->id,
                ]);
            });
        });
        return [
            $categories,
            $property,
            $providers,
            $services,
        ];
    }


    public static function exportRequestData(
        $categories,
        $properties,
        $providers,
        $services
    )
    {
        return [
            'data' => [
                [
                    'export_type' => 'category',
                    'export_data' => $categories->toArray(),
                ],
                [
                    'export_type' => 'property',
                    'export_data' => $properties->toArray(),
                ],
                [
                    'export_type' => 'service',
                    'export_data' => $services->toArray(),
                ],
                [
                    'export_type' => 'provider',
                    'export_data' => $providers->toArray(),
                ]
            ]
        ];
    }

    public function mappingsData(
        $categories,
        $properties,
        $providers,
        $services,
        ImportAction $importAction
    )
    {
//        {
//            "mapping": {
//                "name": "self_no_children",
//                    "label": "Import category",
//                    "dest": null,
//                    "required_fields": [
//                    "id",
//                    "name",
//                    "label"
//                ],
//                "source": "category"
//            },
//            "action": "overwrite",
//            "root": true,
//            "import_type": "category",
//            "label": "Categories",
//        }
        $mappings = [];
        $categoryMapping = [
            'mapping' => [
                'name' => 'self_no_children',
                'label' => 'Import category',
                'dest' => null,
                'required_fields' => [
                    'id',
                    'name',
                    'label'
                ],
                'source' => 'category'
            ],
            'action' => $importAction->value,
            'root' => true,
            'import_type' => 'category',
            'label' => 'Categories',
        ];
        $categoryMapping['children'] = $categories->toArray()->map(function ($category) {
            return [
                'mapping' => [
                    'name' => 'self_no_children',
                    'label' => 'Import category',
                    'dest' => null,
                    'required_fields' => [
                        'id',
                        'name',
                        'label'
                    ],
                    'source' => 'category'
                ],
                'id' => $category['id'],
                'name' => $category['name'],
                'label' => $category['label'],
            ];
        });
        $mappings[] = $categoryMapping;

        foreach ($providers->toArray() as $index => $provider) {
            if ($index === 0) {
                $mappings[] = [
                    'mapping' => [
                        'name' => 'self_no_children',
                        'label' => 'Import provider',
                        'dest' => null,
                        'required_fields' => [
                            'id',
                            'name',
                            'label'
                        ],
                        'source' => 'provider'
                    ],
                    'action' => $importAction->value,
                    'id' => $provider['id'],
                    'name' => $provider['name'],
                    'label' => $provider['label'],
                ];
                continue;
            }
            $mappings[] = [
                'mapping' => [
                    'name' => 'self_with_children',
                    'label' => 'Import provider',
                    'dest' => null,
                    'required_fields' => [
                        'id',
                        'name',
                        'label'
                    ],
                    'source' => 'provider'
                ],
                'action' => $importAction->value,
                'id' => $provider['id'],
                'name' => $provider['name'],
                'label' => $provider['label'],
            ];
        }

        foreach ($providers->toarray() as $provider) {
            foreach ($provider['srs'] as $srIndex => $sr) {

                if ($srIndex === 0) {
                    $mappings[] = [
                        'mapping' => [
                            'name' => 'self_no_children',
                            'label' => 'Import service request',
                            'dest' => 'provider',
                            'required_fields' => [
                                'id',
                                'name',
                                'label'
                            ],
                            'source' => 'sr'
                        ],
                        'action' => $importAction->value,
                        'id' => $sr['id'],
                        'name' => $sr['name'],
                        'label' => $sr['label'],
                    ];
                    continue;
                }
                $mappings[] = [
                    'mapping' => [
                        'name' => 'self_with_children',
                        'label' => 'Import service request',
                        'dest' => 'provider',
                        'required_fields' => [
                            'id',
                            'name',
                            'label'
                        ],
                        'source' => 'sr'
                    ],
                    'action' => $importAction->value,
                    'id' => $sr['id'],
                    'name' => $sr['name'],
                    'label' => $sr['label'],
                ];
            }
        }

        return $mappings;
    }
}
