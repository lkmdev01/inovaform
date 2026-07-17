<?php

namespace Database\Factories;

use App\Models\Funnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FunnelStage>
 */
class FunnelStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funnel_id' => Funnel::factory(),
            'name' => fake()->words(2, true),
            'stage_order' => fake()->numberBetween(1, 6),
            'conversion_rate' => fake()->randomFloat(2, 1, 90),
            'expected_volume' => fake()->numberBetween(10, 1000),
            'meta' => null,
        ];
    }
}
