<?php

use App\Models\Funnel;
use App\Models\FunnelTemplate;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function groqStrategyResponse(): array
{
    return [
        'id' => 'chatcmpl-strategy-test',
        'model' => 'openai/gpt-oss-20b',
        'choices' => [
            [
                'message' => [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'name_suggestion' => 'Diagnóstico Financeiro Inteligente',
                        'objective_summary' => 'Qualificar empresas e entregar um diagnóstico inicial.',
                        'rationale' => 'A sequência reduz atrito, identifica o cenário e captura o contato antes do resultado.',
                        'stages' => [
                            [
                                'name' => 'Contexto',
                                'purpose' => 'Entender o perfil da empresa.',
                                'desired_outcome' => 'Identificar o porte do negócio.',
                                'recommended_block_types' => ['content_text', 'single_choice'],
                            ],
                            [
                                'name' => 'Objetivo',
                                'purpose' => 'Descobrir o principal desafio e capturar o contato.',
                                'desired_outcome' => 'Registrar a prioridade e o e-mail.',
                                'recommended_block_types' => ['textarea', 'email'],
                            ],
                            [
                                'name' => 'Resultado',
                                'purpose' => 'Concluir o diagnóstico e apresentar o próximo passo.',
                                'desired_outcome' => 'Entregar o resultado e identificar interesse comercial.',
                                'recommended_block_types' => ['yes_no'],
                            ],
                        ],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ],
        'usage' => [
            'prompt_tokens' => 110,
            'completion_tokens' => 220,
        ],
    ];
}

function groqFunnelResponse(): array
{
    return [
        'id' => 'chatcmpl-test',
        'model' => 'openai/gpt-oss-20b',
        'choices' => [
            [
                'message' => [
                    'role' => 'assistant',
                    'content' => json_encode([
                        'name' => 'Diagnóstico Financeiro Inteligente',
                        'description' => 'Qualifique empresas interessadas em melhorar a gestão financeira.',
                        'stages' => [
                            [
                                'name' => 'Contexto',
                                'title' => 'Vamos entender sua empresa',
                                'subtitle' => 'Responda duas perguntas rápidas.',
                                'button_text' => 'Começar',
                                'blocks' => [
                                    [
                                        'type' => 'content_text',
                                        'label' => '',
                                        'placeholder' => 'Diagnóstico <script>alert(1)</script> personalizado',
                                        'required' => false,
                                        'options' => [],
                                    ],
                                    [
                                        'type' => 'single_choice',
                                        'label' => 'Qual é o tamanho da sua equipe?',
                                        'placeholder' => '',
                                        'required' => true,
                                        'options' => ['1 a 5 pessoas', '6 a 20 pessoas', 'Mais de 20 pessoas'],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Objetivo',
                                'title' => 'Qual é sua prioridade?',
                                'subtitle' => 'Selecione o desafio mais importante agora.',
                                'button_text' => 'Continuar',
                                'blocks' => [
                                    [
                                        'type' => 'textarea',
                                        'label' => 'Conte um pouco sobre o desafio',
                                        'placeholder' => 'Escreva sua resposta',
                                        'required' => true,
                                        'options' => [],
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Resultado',
                                'title' => 'Seu diagnóstico está pronto',
                                'subtitle' => 'Revise seus dados para receber o resultado.',
                                'button_text' => 'Ver resultado',
                                'blocks' => [
                                    [
                                        'type' => 'yes_no',
                                        'label' => 'Deseja falar com um especialista?',
                                        'placeholder' => '',
                                        'required' => true,
                                        'options' => ['Sim', 'Não'],
                                    ],
                                ],
                            ],
                        ],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ],
        'usage' => [
            'prompt_tokens' => 210,
            'completion_tokens' => 480,
        ],
    ];
}

function groqFunnelResponseWithDuplicateQuestion(): array
{
    $response = groqFunnelResponse();
    $content = json_decode(
        $response['choices'][0]['message']['content'],
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $content['stages'][1]['blocks'][0]['label'] = 'Qual é o tamanho da sua equipe?';
    $response['choices'][0]['message']['content'] = json_encode($content, JSON_THROW_ON_ERROR);

    return $response;
}

function configureGroqForTests(): void
{
    config()->set([
        'services.groq.api_key' => 'groq-test-secret',
        'services.groq.base_url' => 'https://api.groq.com/openai/v1',
        'services.groq.model' => 'openai/gpt-oss-20b',
        'services.groq.retry_delays' => [],
    ]);
}

test('authenticated user can generate only a draft funnel with automatic stages from groq', function () {
    configureGroqForTests();
    Http::preventStrayRequests();
    Http::fakeSequence('https://api.groq.com/openai/v1/chat/completions')
        ->push(groqStrategyResponse())
        ->push(groqFunnelResponse());
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('funnels.ai.store'), [
        'name' => 'Meu diagnóstico de caixa',
        'goal_type' => 'diagnosis',
        'offer' => 'Diagnóstico financeiro para pequenas empresas',
        'pain_point' => 'Falta de clareza sobre o fluxo de caixa',
        'desired_action' => 'receive_result',
        'prompt' => 'Crie um diagnóstico para qualificar pequenas empresas com problemas de caixa.',
        'audience' => 'Donos de pequenas empresas brasileiras',
        'tone' => 'consultivo',
        'target_leads' => 750,
    ]);

    $funnel = Funnel::query()->whereBelongsTo($user)->sole();
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('funnels.builder', $funnel));
    expect($funnel->name)->toBe('Meu diagnóstico de caixa')
        ->and($funnel->is_active)->toBeFalse()
        ->and($funnel->target_leads)->toBe(750)
        ->and($funnel->stages()->count())->toBe(3)
        ->and(FunnelTemplate::query()->count())->toBe(0);

    expect(data_get($funnel->design_settings, 'aiGeneration.quality_score'))->toBe(100)
        ->and(data_get($funnel->design_settings, 'aiGeneration.rationale'))->toContain('reduz atrito')
        ->and(data_get($funnel->design_settings, 'aiGeneration.stage_plan'))->toHaveCount(3)
        ->and(data_get($funnel->design_settings, 'aiGeneration.correction_applied'))->toBeFalse();

    $blocks = $funnel->stages()
        ->orderBy('stage_order')
        ->get()
        ->flatMap(fn ($stage) => data_get($stage->meta, 'builder.blocks', []));

    expect($blocks->pluck('type'))->toContain('email')
        ->and(json_encode($blocks, JSON_THROW_ON_ERROR))->not->toContain('<script>');

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer groq-test-secret')
            && $request['model'] === 'openai/gpt-oss-20b'
            && $request['response_format']['json_schema']['strict'] === true
            && in_array($request['response_format']['json_schema']['name'], [
                'inovaform_funnel_strategy',
                'inovaform_funnel_content',
            ], true);
    });

    $export = $this->actingAs($user)->get(route('funnels.export', $funnel));
    $payload = json_decode($export->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['schema_version'])->toBe(1)
        ->and($payload['funnel']['stages'])->toHaveCount(3)
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('groq-test-secret');
});

test('ai generation audits and corrects a funnel with duplicate questions', function () {
    configureGroqForTests();
    Http::preventStrayRequests();
    Http::fakeSequence('https://api.groq.com/openai/v1/chat/completions')
        ->push(groqStrategyResponse())
        ->push(groqFunnelResponseWithDuplicateQuestion())
        ->push(groqFunnelResponse());
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('funnels.ai.store'), [
            'goal_type' => 'diagnosis',
            'offer' => 'Diagnóstico financeiro',
            'pain_point' => 'Dificuldade para organizar o caixa',
            'desired_action' => 'receive_result',
            'prompt' => 'Considere empresas com equipes pequenas e diferentes níveis de maturidade.',
            'audience' => 'Donos de pequenas empresas',
            'tone' => 'consultivo',
            'target_leads' => 500,
        ])
        ->assertSessionHasNoErrors();

    $funnel = Funnel::query()->whereBelongsTo($user)->sole();
    expect(data_get($funnel->design_settings, 'aiGeneration.correction_applied'))->toBeTrue()
        ->and(data_get($funnel->design_settings, 'aiGeneration.quality_score'))->toBe(100)
        ->and(data_get($funnel->design_settings, 'aiGeneration.quality_notes'))->toBe([]);

    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        return $request['response_format']['json_schema']['name'] === 'inovaform_funnel_correction'
            && str_contains($request['messages'][1]['content'], 'perguntas repetidas');
    });
});

test('ai generation reports missing configuration without calling groq', function () {
    config()->set('services.groq.api_key', null);
    Http::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('funnels.ai.store'), [
            'goal_type' => 'qualification',
            'offer' => 'Consultoria comercial',
            'desired_action' => 'contact',
            'prompt' => 'Crie um funil para qualificar interessados em consultoria comercial.',
            'tone' => 'direto',
            'target_leads' => 500,
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('prompt');

    expect(Funnel::query()->count())->toBe(0)
        ->and(FunnelTemplate::query()->count())->toBe(0);
    Http::assertNothingSent();
});

test('ai generation handles groq rate limits without persisting partial data', function () {
    configureGroqForTests();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'error' => ['type' => 'rate_limit_error'],
        ], 429),
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('funnels.ai.store'), [
            'goal_type' => 'qualification',
            'offer' => 'Consultoria comercial',
            'desired_action' => 'contact',
            'prompt' => 'Crie um funil para qualificar interessados em consultoria comercial.',
            'tone' => 'direto',
            'target_leads' => 500,
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('prompt');

    expect(Funnel::query()->count())->toBe(0)
        ->and(FunnelTemplate::query()->count())->toBe(0);
});

test('ai generation validates the guided brief before making an external request', function () {
    configureGroqForTests();
    Http::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('funnels.ai.store'), [
            'goal_type' => 'qualification',
            'offer' => '',
            'desired_action' => 'contact',
            'tone' => 'direto',
        ])
        ->assertSessionHasErrors('offer');

    Http::assertNothingSent();
});

test('guests cannot call the ai generation endpoint', function () {
    $this->post(route('funnels.ai.store'), [
        'goal_type' => 'qualification',
        'offer' => 'Serviço profissional',
        'desired_action' => 'contact',
        'prompt' => 'Crie um funil de qualificação para um serviço profissional.',
        'tone' => 'direto',
    ])->assertRedirect(route('login'));
});
