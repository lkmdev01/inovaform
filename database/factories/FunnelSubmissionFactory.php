<?php

namespace Database\Factories;

use App\Models\Funnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FunnelSubmission>
 */
class FunnelSubmissionFactory extends Factory
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
            'status' => fake()->randomElement(['new', 'contacted', 'qualified', 'lost']),
            'lead_name' => fake()->name(),
            'lead_email' => fake()->safeEmail(),
            'lead_phone' => fake()->e164PhoneNumber(),
            'session_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'meta' => null,
            'submitted_at' => fake()->dateTimeBetween('-20 days'),
        ];
    }
}
