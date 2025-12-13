<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SrSchedule>
 */
class SrScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_date = null;
        $end_date = null;
        $has_start_date = $this->faker->boolean;
        $has_end_date = $this->faker->boolean;
        if ($has_start_date) {
            $start_date = Carbon::make($this->faker->dateTime());
        }
        if ($has_end_date && $has_start_date) {
            $end_date = $start_date->addDays(2);
        } elseif ($has_end_date) {
            $end_date = Carbon::make($this->faker->dateTime());
        }
        return [
            'execute_immediately' => $this->faker->boolean,
            'forever' => $this->faker->boolean,
            'disabled' => $this->faker->boolean,
            'disable_child_srs' => $this->faker->boolean,
            'priority' => $this->faker->randomNumber(),
            'has_start_date' => $has_start_date,
            'start_date' => $start_date,
            'has_end_date' => $has_end_date,
            'end_date' => $end_date,
            'use_cron_expression' => $this->faker->boolean,
            'cron_expression' => null,
            'every_minute' => $this->faker->boolean,
            'minute' => $this->faker->randomNumber(1, 59),
            'every_hour' => $this->faker->boolean,
            'hour' => $this->faker->randomNumber(1, 23),
            'every_day' => $this->faker->boolean,
            'day' => $this->faker->randomNumber(1, 31),
            'every_weekday' => $this->faker->boolean,
            'weekday' => $this->faker->randomElement([0, 1, 2, 3, 4, 5, 6]),
            'every_month' => $this->faker->boolean,
            'month' => $this->faker->randomNumber(1, 12),
            'parameters' => $this->faker->randomElement(['a', 'b', 'c']),
        ];
    }
}
