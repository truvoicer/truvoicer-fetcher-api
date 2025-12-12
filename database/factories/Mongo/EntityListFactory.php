<?php

namespace Database\Factories\Mongo;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EntityListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(3, true),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'excerpt' => $this->faker->sentence(),
            'keywords' => $this->faker->words(5, true),
            'website' => $this->faker->url(),
            'application_count' => $this->faker->numberBetween(0, 1000),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
            'item_id' => null, // You'll need to set this based on your relationships
            'location_name' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'external_url' => $this->faker->url(),
            'date_added' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'application_deadline' => $this->faker->dateTimeBetween('now', '+6 months'),
            'date_expires' => $this->faker->dateTimeBetween('+1 month', '+1 year'),'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the model is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the model is not featured.
     */
    public function notFeatured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => false,
        ]);
    }

    /**
     * Indicate that the model is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_expires' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'is_active' => false,
        ]);
    }

}
