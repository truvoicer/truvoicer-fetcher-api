<?php

namespace App\Services\Provider;

use Truvoicer\TruFetcherGet\Models\Property;
use Truvoicer\TruFetcherGet\Repositories\ProviderPropertyRepository;
use App\Services\ApiManager\Data\DefaultData;
use Illuminate\Support\Facades\DB;

class PrioritisedProviderProperty
{

    public function __construct(
        private ProviderPropertyRepository $providerPropertyRepository
    ) {}

    public function getPrioritisedProviderProperties(array $providers)
    {
        $properties = array_map(function ($property) {
            return $property['name'];
        }, DefaultData::getProviderProperties());

        $providerIds = array_column($providers, 'id');

        // Create a database-agnostic priority string
        $cases = [];
        foreach ($providerIds as $index => $id) {
            $priority = $index + 1;
            $cases[] = "WHEN {$id} THEN {$priority}";
        }
        $priorityOrderSql = "CASE pp.provider_id " . implode(' ', $cases) . " ELSE " . (count($providerIds) + 1) . " END";

        $baseQuery = Property::query()
            ->whereIn('properties.name', $properties)
            ->addSelect([
                'prioritised_provider_property_id' => DB::table('provider_properties as pp')
                    ->select('pp.id')
                    ->whereColumn('pp.property_id', 'properties.id')
                    ->whereIn('pp.provider_id', $providerIds)
                    ->orderByRaw($priorityOrderSql) // Uses the portable CASE statement
                    ->limit(1)
            ]);

        // 3. Wrap the base query in a subquery to allow filtering on the alias
        $wrappedQuery = DB::query()
            ->fromSub($baseQuery, 't')
            // Now you can safely filter on the aliased column 'prioritised_provider_property_id'
            ->whereNotNull('t.prioritised_provider_property_id')
            ->orderBy('t.id', 'asc'); // Keep your original ordering

        // 4. Get the results and eager load the relation separately
        // This requires getting the IDs first or mapping the results.
        // The easiest way is to let Eloquent handle the loading after fetching the IDs.

        // Convert results back to Property models and load the relation
        $results = $wrappedQuery->get();

        $propertyIds = $results->pluck('id');

        $finalResults = Property::whereIn('id', $propertyIds)
            ->with(['providerProperty' => function ($query) use ($results) {
                // Eager load only the specific providerProperty for each Property
                $query->whereIn(
                    'provider_properties.id',
                    $results->pluck('prioritised_provider_property_id')
                );
            }])
            ->get()
            // Ensure the final collection is in the correct order (optional)
            ->sortBy(function ($model) use ($propertyIds) {
                return array_search($model->getKey(), $propertyIds->toArray());
            })
            ->values();
        return $finalResults;
    }
}
