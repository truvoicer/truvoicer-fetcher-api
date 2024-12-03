<?php

namespace Database\Factories;

use App\Services\ApiManager\Data\DataConstants;
use App\Services\EntityService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = $this->faker->word;
        $valueChoices = null;
        $entities = null;
        $valueType = $this->faker->randomElement(DataConstants::REQUEST_CONFIG_VALUE_TYPES);
        if ($valueType === DataConstants::REQUEST_CONFIG_VALUE_TYPE_CHOICE) {
            $valueChoices = $this->faker->words;
        } elseif ($valueType === DataConstants::REQUEST_CONFIG_VALUE_TYPE_ENTITY_LIST) {
            $entities = EntityService::ENTITIES;
        }
        return [
            'name' => Str::slug($label),
            'label' => $label,
            'value_type' => $valueType,
            'value_choices' => $valueChoices,
            'entities' => $entities,
        ];
    }
}
