<?php

namespace Database\Factories;

use Truvoicer\TfDbReadCore\Enums\Entity\EntityType;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\EntityService;
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
            $entities = array_map(
                fn(EntityType $entityType) => $entityType->value,
                EntityType::cases()
            );
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
