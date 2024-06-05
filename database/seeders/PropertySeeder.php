<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\Property\PropertyService;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(PropertyService $propertyService): void
    {
        foreach ($propertyService->getPropertyRepository()->findMany() as $property) {
            $findByName = in_array($property->name, array_column(DefaultData::getProviderProperties(), 'name'));
            if ($findByName === false) {
                $property->delete();
            }
        }
        foreach (DefaultData::getProviderProperties() as $data) {
            $findProperty = $propertyService->getPropertyRepository()->findOneBy([['name', '=', $data['name']]]);
            $saveData = [];
            if (isset($data['name'])) {
                $saveData['name'] = $data['name'];
            }
            if (isset($data['label'])) {
                $saveData['label'] = $data['label'];
            }
            if (isset($data['value_type'])) {
                $saveData['value_type'] = $data['value_type'];
            }
            if (isset($data['value_choices'])) {
                $saveData['value_choices'] = $data['value_choices'];
            }
            if (empty($saveData)) {
                continue;
            }
            if ($findProperty instanceof Property) {
                $save = $findProperty->update($saveData);
            } else {
                $save = $propertyService->createProperty($saveData);
            }
            if (!$save) {
                throw new \Exception(
                    sprintf(
                        "Property not created | name: %s | label: %s",
                        (isset($data['name']))? $data['name'] : '',
                        (isset($data['label']))? $data['label'] : ''
                    )
                );
            }
        }
    }
}
