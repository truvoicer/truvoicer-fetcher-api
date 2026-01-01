<?php

namespace Database\Seeders;

use Truvoicer\TruFetcherGet\Models\Property;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\Property\PropertyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(PropertyService $propertyService): void
    {
        $providerProperties = DefaultData::getProviderProperties();
        $providerNames = array_column($providerProperties, 'name');

        // Create a comma-separated list of names for SQL query (for databases that support it)
        $lowercaseProviderNames = array_map('strtolower', $providerNames);
        $uppercaseProviderNames = array_map('strtoupper', $providerNames);

        // Delete properties that don't match any provider name (case-insensitive)
        Property::where(function ($query) use ($providerNames, $lowercaseProviderNames, $uppercaseProviderNames) {
            // For each property, check if it matches any provider name case-insensitively
            $query->whereNotIn('name', $providerNames)
                ->whereNotIn(DB::raw('LOWER(name)'), $lowercaseProviderNames)
                ->whereNotIn(DB::raw('UPPER(name)'), $uppercaseProviderNames);
        })->delete();

        foreach ($providerProperties as $data) {
            if (!isset($data['name'])) {
                continue;
            }

            $providerName = $data['name'];

            // Find property case-insensitively using database functions
            $findProperty = Property::where('name', $providerName)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($providerName)])
                ->orWhereRaw('UPPER(name) = ?', [strtoupper($providerName)])
                ->first();

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
            if (isset($data['entities'])) {
                $saveData['entities'] = $data['entities'];
            }

            if (empty($saveData)) {
                continue;
            }

            if ($findProperty instanceof Property) {
                $save = $findProperty->update($saveData);
            } else {
                $save = Property::create($saveData);
            }

            if (!$save) {
                throw new \Exception(
                    sprintf(
                        "Property not created | name: %s | label: %s",
                        $data['name'] ?? '',
                        $data['label'] ?? ''
                    )
                );
            }
        }
    }
}
