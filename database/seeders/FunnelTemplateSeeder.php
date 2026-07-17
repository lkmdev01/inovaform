<?php

namespace Database\Seeders;

use App\Models\FunnelTemplate;
use Illuminate\Database\Seeder;

class FunnelTemplateSeeder extends Seeder
{
    public function run(): void
    {
        collect($this->templates())
            ->each(static function (array $template): void {
                FunnelTemplate::query()->updateOrCreate(
                    ['slug' => $template['slug']],
                    $template,
                );
            });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'user_id' => null,
                'source_funnel_id' => null,
                'name' => 'Captura de Leads',
                'slug' => 'captura-de-leads',
                'description' => 'Template direto para captar nome, email e celular com uma oferta simples.',
                'category' => 'captacao',
                'thumbnail_path' => null,
                'is_system' => true,
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 1,
                'version' => 1,
                'schema' => [
                    'target_leads' => 1500,
                    'design_settings' => [
                        'alignment' => 'center',
                        'width' => 'small',
                        'elementSize' => 'default',
                        'spacing' => 'default',
                        'radius' => 'medium',
                        'showLogo' => true,
                        'showProgress' => true,
                        'allowBack' => true,
                        'accentColor' => '#3d8bff',
                        'pageColor' => '#050d22',
                        'cardColor' => '#0b1a3a',
                        'headingColor' => '#f8fbff',
                        'textColor' => '#a8bfeb',
                        'buttonColor' => '#12356f',
                        'buttonTextColor' => '#e8f2ff',
                        'fontStyle' => 'modern',
                    ],
                    'preview' => [
                        'badge' => 'Mais usado',
                        'accentColor' => '#3d8bff',
                        'headline' => 'Capte leads com uma oferta clara e uma CTA forte.',
                        'chips' => ['Formulario', 'Qualificacao', 'CTA'],
                    ],
                    'stages' => [
                        [
                            'name' => 'Captura',
                            'conversion_rate' => 100,
                            'expected_volume' => 3500,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Receba uma analise rapida do seu negocio',
                                    'subtitle' => 'Preencha seus dados para liberar a primeira recomendacao.',
                                    'button_text' => 'Ver recomendacao',
                                    'blocks' => [
                                        [
                                            'id' => 'lead-name',
                                            'type' => 'text',
                                            'label' => 'Nome',
                                            'placeholder' => 'Digite aqui seu nome...',
                                            'required' => true,
                                        ],
                                        [
                                            'id' => 'lead-email',
                                            'type' => 'email',
                                            'label' => 'E-mail',
                                            'placeholder' => 'Digite seu e-mail...',
                                            'required' => true,
                                        ],
                                        [
                                            'id' => 'lead-phone',
                                            'type' => 'phone',
                                            'label' => 'Telefone',
                                            'placeholder' => 'Digite seu celular...',
                                            'required' => true,
                                            'phone_mask' => 'br',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Qualificacao',
                            'conversion_rate' => 42,
                            'expected_volume' => 1470,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Qual o seu maior gargalo hoje?',
                                    'subtitle' => 'Escolha a opcao que mais representa o momento da sua operacao.',
                                    'button_text' => 'Continuar',
                                    'blocks' => [
                                        [
                                            'id' => 'lead-challenge',
                                            'type' => 'single_choice',
                                            'label' => 'Principal desafio',
                                            'required' => true,
                                            'options_intro_type' => 'none',
                                            'options_detail' => 'checkout',
                                            'option_items' => [
                                                ['id' => 'challenge-1', 'label' => 'Gerar mais leads', 'value' => 'leads', 'destination' => 'next_stage'],
                                                ['id' => 'challenge-2', 'label' => 'Melhorar conversao', 'value' => 'conversion', 'destination' => 'next_stage'],
                                                ['id' => 'challenge-3', 'label' => 'Aumentar ticket medio', 'value' => 'ticket', 'destination' => 'next_stage'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Oferta',
                            'conversion_rate' => 18,
                            'expected_volume' => 264,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Seu lead esta pronto para contato',
                                    'subtitle' => 'Use esta etapa para apresentar uma oferta ou redirecionar para o proximo passo.',
                                    'button_text' => 'Finalizar',
                                    'blocks' => [
                                        [
                                            'id' => 'offer-note',
                                            'type' => 'content_text',
                                            'label' => null,
                                            'placeholder' => '<h2>Pronto para avancar?</h2><p>Voce ja tem os dados principais para iniciar a conversa comercial.</p>',
                                            'required' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'user_id' => null,
                'source_funnel_id' => null,
                'name' => 'Diagnostico Express',
                'slug' => 'diagnostico-express',
                'description' => 'Quiz curto para qualificar o lead e entregar uma resposta personalizada.',
                'category' => 'qualificacao',
                'thumbnail_path' => null,
                'is_system' => true,
                'is_premium' => true,
                'is_active' => true,
                'sort_order' => 2,
                'version' => 1,
                'schema' => [
                    'target_leads' => 1200,
                    'design_settings' => [
                        'alignment' => 'center',
                        'width' => 'small',
                        'elementSize' => 'default',
                        'spacing' => 'default',
                        'radius' => 'medium',
                        'showLogo' => true,
                        'showProgress' => true,
                        'allowBack' => true,
                        'accentColor' => '#29c0ff',
                        'pageColor' => '#050d22',
                        'cardColor' => '#0b1a3a',
                        'headingColor' => '#f8fbff',
                        'textColor' => '#a8bfeb',
                        'buttonColor' => '#12356f',
                        'buttonTextColor' => '#e8f2ff',
                        'fontStyle' => 'modern',
                    ],
                    'preview' => [
                        'badge' => 'Qualificacao',
                        'accentColor' => '#29c0ff',
                        'headline' => 'Descubra o melhor proximo passo com perguntas objetivas.',
                        'chips' => ['Quiz', 'Segmentacao', 'Personalizacao'],
                    ],
                    'stages' => [
                        [
                            'name' => 'Diagnostico',
                            'conversion_rate' => 100,
                            'expected_volume' => 2800,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Qual area precisa de mais atencao agora?',
                                    'subtitle' => 'Escolha a opcao principal para direcionarmos o diagnostico.',
                                    'button_text' => 'Continuar',
                                    'blocks' => [
                                        [
                                            'id' => 'diagnostic-area',
                                            'type' => 'options',
                                            'label' => 'Area principal',
                                            'required' => true,
                                            'options_intro_type' => 'none',
                                            'options_style' => 'simple',
                                            'options_detail' => 'arrow',
                                            'option_items' => [
                                                ['id' => 'diag-1', 'label' => 'Marketing', 'value' => 'marketing', 'destination' => 'next_stage'],
                                                ['id' => 'diag-2', 'label' => 'Vendas', 'value' => 'sales', 'destination' => 'next_stage'],
                                                ['id' => 'diag-3', 'label' => 'Operacao', 'value' => 'operations', 'destination' => 'next_stage'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Objetivos',
                            'conversion_rate' => 55,
                            'expected_volume' => 1540,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Quais metas voce quer acelerar?',
                                    'subtitle' => 'Selecione uma ou mais opcoes para ajustar a resposta.',
                                    'button_text' => 'Continuar',
                                    'blocks' => [
                                        [
                                            'id' => 'diagnostic-goals',
                                            'type' => 'multiple_choice',
                                            'label' => 'Objetivos',
                                            'required' => true,
                                            'options_intro_type' => 'none',
                                            'options_style' => 'simple',
                                            'options_detail' => 'checkout',
                                            'options_allow_multiple' => true,
                                            'option_items' => [
                                                ['id' => 'goal-1', 'label' => 'Gerar demanda', 'value' => 'demand', 'destination' => 'next_stage'],
                                                ['id' => 'goal-2', 'label' => 'Fechar mais vendas', 'value' => 'close', 'destination' => 'next_stage'],
                                                ['id' => 'goal-3', 'label' => 'Ganhar previsibilidade', 'value' => 'predictable', 'destination' => 'next_stage'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Contato',
                            'conversion_rate' => 24,
                            'expected_volume' => 369,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Para onde enviamos o resultado?',
                                    'subtitle' => 'Informe um contato para receber o diagnostico completo.',
                                    'button_text' => 'Receber resultado',
                                    'blocks' => [
                                        [
                                            'id' => 'contact-name',
                                            'type' => 'text',
                                            'label' => 'Nome',
                                            'placeholder' => 'Seu nome',
                                            'required' => true,
                                        ],
                                        [
                                            'id' => 'contact-email',
                                            'type' => 'email',
                                            'label' => 'E-mail',
                                            'placeholder' => 'Seu melhor e-mail',
                                            'required' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'user_id' => null,
                'source_funnel_id' => null,
                'name' => 'Inscricao para Webinar',
                'slug' => 'inscricao-para-webinar',
                'description' => 'Fluxo enxuto para capturar inscricoes e organizar lembretes.',
                'category' => 'evento',
                'thumbnail_path' => null,
                'is_system' => true,
                'is_premium' => false,
                'is_active' => true,
                'sort_order' => 3,
                'version' => 1,
                'schema' => [
                    'target_leads' => 900,
                    'design_settings' => [
                        'alignment' => 'center',
                        'width' => 'small',
                        'elementSize' => 'default',
                        'spacing' => 'default',
                        'radius' => 'medium',
                        'showLogo' => true,
                        'showProgress' => true,
                        'allowBack' => true,
                        'accentColor' => '#7d6bff',
                        'pageColor' => '#050d22',
                        'cardColor' => '#0b1a3a',
                        'headingColor' => '#f8fbff',
                        'textColor' => '#a8bfeb',
                        'buttonColor' => '#12356f',
                        'buttonTextColor' => '#e8f2ff',
                        'fontStyle' => 'modern',
                    ],
                    'preview' => [
                        'badge' => 'Evento',
                        'accentColor' => '#7d6bff',
                        'headline' => 'Abra inscricoes, confirme interesse e organize lembretes.',
                        'chips' => ['Evento', 'Inscricao', 'Follow-up'],
                    ],
                    'stages' => [
                        [
                            'name' => 'Inscricao',
                            'conversion_rate' => 100,
                            'expected_volume' => 2200,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Garanta sua vaga no proximo encontro',
                                    'subtitle' => 'Preencha seus dados para receber o link e o lembrete do evento.',
                                    'button_text' => 'Quero me inscrever',
                                    'blocks' => [
                                        [
                                            'id' => 'webinar-name',
                                            'type' => 'text',
                                            'label' => 'Nome',
                                            'placeholder' => 'Como podemos te chamar?',
                                            'required' => true,
                                        ],
                                        [
                                            'id' => 'webinar-email',
                                            'type' => 'email',
                                            'label' => 'E-mail',
                                            'placeholder' => 'Seu melhor e-mail',
                                            'required' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Lembrete',
                            'conversion_rate' => 48,
                            'expected_volume' => 1056,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Quer receber um lembrete no celular?',
                                    'subtitle' => 'Escolha como prefere ser avisado antes do evento.',
                                    'button_text' => 'Confirmar',
                                    'blocks' => [
                                        [
                                            'id' => 'webinar-phone',
                                            'type' => 'phone',
                                            'label' => 'Telefone',
                                            'placeholder' => 'Seu celular',
                                            'required' => false,
                                            'phone_mask' => 'br',
                                        ],
                                        [
                                            'id' => 'webinar-reminder',
                                            'type' => 'yes_no',
                                            'label' => 'Receber lembrete',
                                            'required' => true,
                                            'options_intro_type' => 'none',
                                            'options_detail' => 'none',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Confirmacao',
                            'conversion_rate' => 30,
                            'expected_volume' => 317,
                            'meta' => [
                                'header' => [
                                    'show_logo' => true,
                                    'show_progress' => true,
                                    'allow_back' => true,
                                ],
                                'builder' => [
                                    'title' => 'Inscricao confirmada',
                                    'subtitle' => 'Agora voce pode reforcar a chamada para um grupo, calendario ou oferta complementar.',
                                    'button_text' => 'Concluir',
                                    'blocks' => [
                                        [
                                            'id' => 'webinar-confirmation',
                                            'type' => 'alert',
                                            'label' => null,
                                            'placeholder' => 'Envie o link do evento e mantenha o lead aquecido ate a data da transmissao.',
                                            'required' => false,
                                            'alert_style' => 'blue',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
