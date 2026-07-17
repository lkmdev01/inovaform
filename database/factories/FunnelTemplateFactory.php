<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FunnelTemplate>
 */
class FunnelTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'user_id' => null,
            'source_funnel_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['captacao', 'qualificacao', 'evento']),
            'thumbnail_path' => null,
            'is_system' => true,
            'is_premium' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'version' => 1,
            'schema' => [
                'target_leads' => fake()->numberBetween(300, 3000),
                'design_settings' => [
                    'alignment' => 'center',
                    'width' => 'small',
                    'elementSize' => 'default',
                    'spacing' => 'default',
                    'radius' => 'medium',
                    'showLogo' => true,
                    'showProgress' => true,
                    'allowBack' => true,
                ],
                'preview' => [
                    'badge' => 'Modelo',
                    'accentColor' => '#3d8bff',
                    'headline' => fake()->sentence(4),
                    'chips' => ['Formulario', 'CTA'],
                ],
                'stages' => [
                    [
                        'name' => 'Etapa 1',
                        'conversion_rate' => 100,
                        'expected_volume' => 1000,
                        'meta' => [
                            'header' => [
                                'show_logo' => true,
                                'show_progress' => true,
                                'allow_back' => true,
                            ],
                            'builder' => [
                                'title' => 'Titulo',
                                'subtitle' => 'Subtitulo',
                                'button_text' => 'Continuar',
                                'blocks' => [
                                    [
                                        'id' => 'text-1',
                                        'type' => 'text',
                                        'label' => 'Nome',
                                        'placeholder' => 'Digite seu nome',
                                        'required' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Etapa 2',
                        'conversion_rate' => 35,
                        'expected_volume' => 350,
                        'meta' => [
                            'header' => [
                                'show_logo' => true,
                                'show_progress' => true,
                                'allow_back' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'is_system' => false,
            'is_premium' => false,
        ]);
    }
}
