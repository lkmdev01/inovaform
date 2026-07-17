<?php

namespace Database\Factories;

use App\Models\FunnelStage;
use App\Models\FunnelSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FunnelSubmissionAnswer>
 */
class FunnelSubmissionAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'funnel_submission_id' => FunnelSubmission::factory(),
            'funnel_stage_id' => FunnelStage::factory(),
            'block_id' => fake()->uuid(),
            'block_type' => fake()->randomElement(['text', 'email', 'phone', 'single_choice']),
            'block_label' => fake()->words(3, true),
            'value' => [fake()->sentence()],
        ];
    }
}
