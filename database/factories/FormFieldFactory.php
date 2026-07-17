<?php

namespace Database\Factories;

use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_template_id' => FormTemplate::factory(),
            'type' => fake()->randomElement(['text', 'email', 'phone', 'textarea']),
            'label' => fake()->words(2, true),
            'placeholder' => fake()->optional()->sentence(3),
            'is_required' => fake()->boolean(),
            'sort_order' => fake()->numberBetween(1, 10),
            'options' => null,
        ];
    }
}
