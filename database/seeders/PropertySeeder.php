<?php

namespace Database\Seeders;

use App\Library\Defaults\DefaultData;
use App\Models\Property;
use App\Services\Property\PropertyService;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(PropertyService $propertyService): void
    {
        //
        foreach (DefaultData::getProviderProperties() as $data) {
            $findProperty = $propertyService->getPropertyRepository()->findOneBy([['name', '=', $data['name']]]);
            if ($findProperty instanceof Property) {
                continue;
            }
            $save = $propertyService->createProperty([
                'name' => $data['name'],
                'label' => $data['label'],
                'value_type' => $data['value_type'],
                'value_choices' => $data['value_choices'],
            ]);
            if (!$save) {
                throw new \Exception(
                    "Property not created | name: {$data['name']} | label: {$data['label']}"
                );
            }
        }
    }
}
