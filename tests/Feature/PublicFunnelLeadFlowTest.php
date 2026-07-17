<?php

use App\Jobs\ProcessFunnelSubmissionJob;
use App\Models\Funnel;
use App\Models\FunnelSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function makePublicFunnel(User $owner): Funnel
{
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Publico',
        'slug' => 'funil-publico',
        'is_active' => true,
        'design_settings' => [
            'alignment' => 'left',
            'width' => 'medium',
            'elementSize' => 'compact',
            'spacing' => 'default',
            'radius' => 'small',
            'showLogo' => true,
            'showProgress' => false,
            'allowBack' => false,
            'accentColor' => '#2244aa',
            'pageColor' => '#010203',
            'cardColor' => '#040b22',
            'headingColor' => '#f8fbff',
            'textColor' => '#9db7e9',
            'buttonColor' => '#12356f',
            'buttonTextColor' => '#ffffff',
            'fontStyle' => 'clean',
        ],
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'header' => [
                    'show_logo' => false,
                    'show_progress' => true,
                    'allow_back' => false,
                ],
                'builder' => [
                    'title' => '',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        ['id' => 'chart_block', 'type' => 'charts', 'label' => 'Grafico legado', 'required' => false],
                        ['id' => 'name_block', 'type' => 'text', 'label' => 'Nome', 'required' => true],
                        ['id' => 'email_block', 'type' => 'email', 'label' => 'Email', 'required' => true],
                        ['id' => 'arguments_block', 'type' => 'arguments', 'label' => null, 'required' => false, 'options' => ['Prova social validada', 'Metodo estruturado']],
                        ['id' => 'before_after_block', 'type' => 'before_after', 'label' => null, 'required' => false, 'options' => ['Situacao atual', 'Resultado esperado']],
                        ['id' => 'image_block', 'type' => 'image', 'label' => 'Hero', 'placeholder' => '/storage/funnels/teste.png', 'required' => false, 'image_ratio' => '16:9', 'image_fit' => 'contain', 'image_radius' => 'large', 'image_frame' => 'strong'],
                        ['id' => 'video_block', 'type' => 'video', 'label' => 'Video', 'placeholder' => 'https://www.youtube.com/embed/abc123', 'required' => false, 'video_ratio' => '4:3'],
                        ['id' => 'audio_block', 'type' => 'audio', 'label' => 'Audio', 'required' => false, 'audio_sender' => 'Joao Silva', 'audio_src' => '/storage/funnels/audio.mp3', 'audio_model' => 'whatsapp', 'audio_theme' => 'light'],
                    ],
                ],
                ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => '',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        ['id' => 'phone_block', 'type' => 'phone', 'label' => 'Telefone', 'required' => false, 'phone_mask' => 'us'],
                        ['id' => 'number_block', 'type' => 'number', 'label' => 'Orcamento', 'required' => false, 'number_mask' => 'euro'],
                        ['id' => 'challenge_block', 'type' => 'single_choice', 'label' => 'Desafio', 'required' => true, 'options' => ['Leads', 'Vendas']],
                    ],
                ],
            ],
        ],
    ]);

    return $funnel->fresh();
}

test('app home still renders welcome page on default host', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('public active funnel can be viewed by slug', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.slug', $funnel->slug)
            ->where('funnel.design.alignment', 'left')
            ->where('funnel.design.showProgress', false)
            ->where('funnel.design.tokens.colors.primary', '#2244aa')
            ->where('funnel.design.tokens.typography.family', 'clean')
            ->where('funnel.design.tokens.surfaces.page', '#010203')
            ->where('funnel.design.tokens.surfaces.card', '#040b22')
            ->where('funnel.design.tokens.components.primaryButtonBackground', 'linear-gradient(135deg, #2563eb, #06b6d4)')
            ->where('funnel.stages.0.header.show_logo', false)
            ->has('funnel.stages.0.blocks', 7)
            ->where('funnel.stages.0.blocks.2.type', 'arguments')
            ->where('funnel.stages.0.blocks.3.type', 'before_after')
            ->where('funnel.stages.0.blocks.4.image_ratio', '16:9')
            ->where('funnel.stages.0.blocks.4.image_frame', 'strong')
            ->where('funnel.stages.0.blocks.5.video_ratio', '4:3')
            ->where('funnel.stages.0.blocks.6.audio_src', '/storage/funnels/audio.mp3')
            ->where('funnel.stages.0.blocks.6.audio_sender', 'Joao Silva')
            ->where('funnel.stages.1.blocks.0.phone_mask', 'us')
            ->where('funnel.stages.1.blocks.1.number_mask', 'euro')
            ->has('funnel.stages', 2)
        );
});

test('public funnel payload includes configured completion page settings', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil com conclusao',
        'slug' => 'funil-com-conclusao',
        'is_active' => true,
        'design_settings' => [
            'completion_page' => [
                'enabled' => true,
                'title' => 'Obrigado, {nome}',
                'description' => 'Recebemos seu envio.',
                'image_url' => 'https://example.com/success.png',
                'primary_button_text' => 'Ir para o site',
                'primary_button_url' => 'https://example.com',
                'primary_button_new_tab' => true,
                'secondary_button_text' => 'Voltar',
                'secondary_button_url' => '/',
                'secondary_button_new_tab' => false,
                'auto_redirect_url' => 'https://example.com/redirect',
                'auto_redirect_delay_seconds' => 4,
            ],
        ],
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => '',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => '',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.design.completion_page.enabled', true)
            ->where('funnel.design.completion_page.title', 'Obrigado, {nome}')
            ->where('funnel.design.completion_page.description', 'Recebemos seu envio.')
            ->where('funnel.design.completion_page.image_url', 'https://example.com/success.png')
            ->where('funnel.design.completion_page.primary_button_text', 'Ir para o site')
            ->where('funnel.design.completion_page.primary_button_url', 'https://example.com')
            ->where('funnel.design.completion_page.primary_button_new_tab', true)
            ->where('funnel.design.completion_page.secondary_button_text', 'Voltar')
            ->where('funnel.design.completion_page.secondary_button_url', '/')
            ->where('funnel.design.completion_page.secondary_button_new_tab', false)
            ->where('funnel.design.completion_page.auto_redirect_url', 'https://example.com/redirect')
            ->where('funnel.design.completion_page.auto_redirect_delay_seconds', 4)
        );
});

test('custom domain root serves the published funnel with domain submit url', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->update([
        'custom_domain' => 'quiz.cliente.com',
        'design_settings' => array_merge($funnel->design_settings ?? [], [
            'logoUrl' => 'https://example.com/logo.png',
            'faviconUrl' => 'https://example.com/favicon.png',
            'seoTitle' => 'SEO customizado',
            'seoDescription' => 'Descricao SEO personalizada',
            'seoImageUrl' => 'https://example.com/og.png',
        ]),
    ]);

    $this->get('http://quiz.cliente.com/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.slug', $funnel->slug)
            ->where('funnel.submit_url', '/submit')
            ->where('funnel.using_custom_domain', true)
            ->where('funnel.custom_domain', 'quiz.cliente.com')
            ->where('funnel.design.logoUrl', 'https://example.com/logo.png')
            ->where('funnel.design.faviconUrl', 'https://example.com/favicon.png')
            ->where('funnel.design.seoTitle', 'SEO customizado')
            ->where('funnel.design.seoDescription', 'Descricao SEO personalizada')
            ->where('funnel.design.seoImageUrl', 'https://example.com/og.png')
        );
});

test('inactive public funnel renders refined unavailable page', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->update([
        'is_active' => false,
        'design_settings' => array_merge($funnel->design_settings ?? [], [
            'unavailableTitle' => 'Funil fechado',
            'unavailableDescription' => 'Volte depois.',
        ]),
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicUnavailable')
            ->where('statusCode', 404)
            ->where('title', 'Funil fechado')
            ->where('description', 'Volte depois.')
            ->where('funnel.slug', $funnel->slug)
        );
});

test('expired custom domain funnel renders gone unavailable page', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->update([
        'custom_domain' => 'expirado.cliente.com',
        'design_settings' => array_merge($funnel->design_settings ?? [], [
            'expiresAt' => now()->subMinute()->toISOString(),
            'unavailableTitle' => 'Campanha encerrada',
            'unavailableDescription' => 'Este link expirou.',
        ]),
    ]);

    $this->get('http://expirado.cliente.com/')
        ->assertStatus(410)
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicUnavailable')
            ->where('statusCode', 410)
            ->where('title', 'Campanha encerrada')
            ->where('description', 'Este link expirou.')
            ->where('funnel.custom_domain', 'expirado.cliente.com')
        );
});

test('public active funnel can be viewed with empty draft content', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Draft Publico',
        'slug' => 'funil-draft-publico',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => null,
                'subtitle' => null,
                'button_text' => null,
                'blocks' => [
                    ['id' => 'draft-text', 'type' => 'content_text', 'label' => null, 'placeholder' => null, 'required' => false],
                    [
                        'id' => 'draft-options',
                        'type' => 'single_choice',
                        'label' => null,
                        'required' => false,
                        'options' => [''],
                        'option_items' => [
                            ['id' => 'draft-option-1', 'label' => null, 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                        ],
                        'options_intro_type' => 'none',
                        'options_intro_title' => null,
                        'options_intro_description' => null,
                    ],
                    ['id' => 'draft-button', 'type' => 'button', 'label' => null, 'required' => false, 'button_action' => 'next_stage', 'button_target_stage_order' => 'next'],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.slug', $funnel->slug)
            ->has('funnel.stages', 1)
            ->where('funnel.stages.0.blocks.0.label', '')
            ->where('funnel.stages.0.blocks.1.option_items.0.label', '')
        );
});

test('public active funnel exposes option navigation settings in payload', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Navegacao de Opcoes',
        'slug' => 'funil-navegacao-opcoes',
        'is_active' => true,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => '',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        [
                            'id' => 'choice_block',
                            'type' => 'single_choice',
                            'label' => 'Escolha',
                            'required' => true,
                            'options_allow_multiple' => false,
                            'options_disable_auto_follow' => false,
                            'option_items' => [
                                ['id' => 'opt_a', 'label' => 'Primeira', 'points' => 0, 'value' => 'A', 'destination' => 'Etapa 2'],
                                ['id' => 'opt_b', 'label' => 'Segunda', 'points' => 0, 'value' => 'B', 'destination' => '3'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        ['name' => 'Etapa 2', 'stage_order' => 2, 'meta' => ['builder' => ['title' => '', 'subtitle' => '', 'button_text' => '', 'blocks' => []]]],
        ['name' => 'Etapa 3', 'stage_order' => 3, 'meta' => ['builder' => ['title' => '', 'subtitle' => '', 'button_text' => '', 'blocks' => []]]],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.options_allow_multiple', false)
            ->where('funnel.stages.0.blocks.0.options_disable_auto_follow', false)
            ->where('funnel.stages.0.blocks.0.option_items.0.destination', 'Etapa 2')
            ->where('funnel.stages.0.blocks.0.option_items.1.destination', '3')
        );
});

test('public active funnel preserves legacy content text title and description separately', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Texto Legado',
        'slug' => 'funil-texto-legado',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    ['id' => 'legacy-content', 'type' => 'content_text', 'label' => 'Titulo legado', 'placeholder' => 'Descricao legada', 'required' => false],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.label', 'Titulo legado')
            ->where('funnel.stages.0.blocks.0.placeholder', 'Descricao legada')
        );
});

test('public active funnel preserves rich content text markup', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Texto Rico',
        'slug' => 'funil-texto-rico',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'rich-content',
                        'type' => 'content_text',
                        'label' => '',
                        'placeholder' => '<h2>Texto Rico</h2><p><strong>Descricao</strong> com <a href="https://example.com">link</a></p>',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.placeholder', '<h2>Texto Rico</h2><p><strong>Descricao</strong> com <a href="https://example.com">link</a></p>')
        );
});

test('public active funnel preserves content text alignment in payload', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Content Align',
        'slug' => 'funil-content-align',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [[
                    'id' => 'aligned-content',
                    'type' => 'content_text',
                    'label' => null,
                    'placeholder' => '<h2>Conteudo alinhado</h2>',
                    'text_align' => 'text-right',
                    'required' => false,
                ]],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.text_align', 'text-right')
        );
});

test('public active funnel does not invent stage title when builder title is empty', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Sem Titulo',
        'slug' => 'funil-sem-titulo',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa Interna',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => null,
                'subtitle' => null,
                'button_text' => null,
                'blocks' => [],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.name', 'Etapa Interna')
        );
});

test('public active funnel keeps advanced component settings in payload', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Componentes Avancados',
        'slug' => 'funil-componentes-avancados',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    ['id' => 'attention_block', 'type' => 'attention', 'label' => '', 'placeholder' => 'Leia com atenção', 'attention_style' => 'red', 'attention_padding' => 'compact', 'attention_emphasis' => true],
                    ['id' => 'notification_block', 'type' => 'notification', 'label' => '', 'notification_title' => '@1 acabou de se cadastrar via @2!', 'notification_description' => 'Corra! Faltam apenas @3 ofertas disponíveis', 'notification_position' => 'default', 'notification_duration_seconds' => 5, 'notification_interval_seconds' => 2, 'notification_style' => 'white', 'notification_variations' => [['id' => 'var_1', 'value1' => 'Rafael', 'value2' => 'Instagram', 'value3' => '7']]],
                    ['id' => 'loading_block', 'type' => 'loading', 'label' => 'Carregando...', 'placeholder' => 'Processando seus dados', 'loading_start_seconds' => 10, 'loading_duration_seconds' => 8, 'loading_navigation_action' => 'none', 'loading_show_title' => false, 'loading_show_progress' => true, 'width_percent' => 72, 'align_horizontal' => 'center', 'label_style' => 'muted', 'show_after_seconds' => 3, 'display_rule_mode' => 'any', 'display_rules' => ['filled:name_block']],
                    ['id' => 'timer_block', 'type' => 'timer', 'label' => '', 'timer_seconds' => 20, 'timer_text' => 'Resgate [time]', 'timer_style' => 'blue'],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.attention_style', 'red')
            ->where('funnel.stages.0.blocks.0.attention_padding', 'compact')
            ->where('funnel.stages.0.blocks.0.attention_emphasis', true)
            ->where('funnel.stages.0.blocks.1.notification_style', 'white')
            ->where('funnel.stages.0.blocks.1.notification_position', 'default')
            ->where('funnel.stages.0.blocks.1.notification_duration_seconds', 5)
            ->where('funnel.stages.0.blocks.1.notification_interval_seconds', 2)
            ->where('funnel.stages.0.blocks.1.notification_variations.0.value1', 'Rafael')
            ->where('funnel.stages.0.blocks.1.notification_variations.0.value2', 'Instagram')
            ->where('funnel.stages.0.blocks.1.notification_variations.0.value3', '7')
            ->where('funnel.stages.0.blocks.2.loading_start_seconds', 10)
            ->where('funnel.stages.0.blocks.2.loading_duration_seconds', 8)
            ->where('funnel.stages.0.blocks.2.loading_navigation_action', 'none')
            ->where('funnel.stages.0.blocks.2.loading_show_title', false)
            ->where('funnel.stages.0.blocks.2.loading_show_progress', true)
            ->where('funnel.stages.0.blocks.2.width_percent', 72)
            ->where('funnel.stages.0.blocks.2.align_horizontal', 'center')
            ->where('funnel.stages.0.blocks.2.label_style', 'muted')
            ->where('funnel.stages.0.blocks.2.show_after_seconds', 3)
            ->where('funnel.stages.0.blocks.2.display_rule_mode', 'any')
            ->where('funnel.stages.0.blocks.2.display_rule_groups.0.mode', 'any')
            ->where('funnel.stages.0.blocks.2.display_rule_groups.0.rules.0.source_block_id', 'name_block')
            ->where('funnel.stages.0.blocks.2.display_rule_groups.0.rules.0.operator', 'filled')
            ->where('funnel.stages.0.blocks.3.timer_seconds', 20)
            ->where('funnel.stages.0.blocks.3.timer_text', 'Resgate [time]')
            ->where('funnel.stages.0.blocks.3.timer_style', 'blue')
        );
});

test('public payload preserves arrow detail for options blocks', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Opcoes Arrow',
        'slug' => 'funil-opcoes-arrow',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [[
                    'id' => 'options-arrow',
                    'type' => 'single_choice',
                    'label' => '',
                    'required' => false,
                    'options_detail' => 'arrow',
                    'option_items' => [
                        ['id' => 'opt-a', 'label' => 'Opcao A', 'points' => 1, 'value' => 'A', 'destination' => 'next_stage'],
                    ],
                ]],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.options_detail', 'arrow')
        );
});

test('public payload keeps legacy notification and timer fallback fields', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Fallback Timer Notification',
        'slug' => 'funil-fallback-timer-notification',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'legacy-notification',
                        'type' => 'notification',
                        'label' => 'Joao entrou agora',
                        'placeholder' => 'Vagas quase esgotadas',
                        'notification_title' => '',
                        'notification_description' => '',
                    ],
                    [
                        'id' => 'legacy-timer',
                        'type' => 'timer',
                        'label' => 'Oferta acaba em',
                        'placeholder' => '',
                        'timer_seconds' => 20,
                        'timer_text' => '',
                    ],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.label', 'Joao entrou agora')
            ->where('funnel.stages.0.blocks.0.placeholder', 'Vagas quase esgotadas')
            ->where('funnel.stages.0.blocks.0.notification_title', '')
            ->where('funnel.stages.0.blocks.0.notification_description', '')
            ->where('funnel.stages.0.blocks.1.label', 'Oferta acaba em')
            ->where('funnel.stages.0.blocks.1.timer_seconds', 20)
            ->where('funnel.stages.0.blocks.1.timer_text', '')
        );
});

test('public payload preserves option image disposition, detail position, and style', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Opcoes Com Imagem',
        'slug' => 'funil-opcoes-com-imagem',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [[
                    'id' => 'options-image',
                    'type' => 'single_choice',
                    'label' => '',
                    'required' => false,
                    'options_style' => 'relief',
                    'options_layout' => 'grid_1',
                    'options_disposition' => 'text_image',
                    'options_detail' => 'value',
                    'options_detail_position' => 'end',
                    'option_items' => [
                        [
                            'id' => 'opt-a',
                            'label' => 'Opcao A',
                            'points' => 1,
                            'value' => 'A',
                            'destination' => 'next_stage',
                            'image_url' => '/storage/funnels/opcao-a.png',
                        ],
                    ],
                ]],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.options_style', 'relief')
            ->where('funnel.stages.0.blocks.0.options_layout', 'grid_1')
            ->where('funnel.stages.0.blocks.0.options_disposition', 'text_image')
            ->where('funnel.stages.0.blocks.0.options_detail', 'value')
            ->where('funnel.stages.0.blocks.0.options_detail_position', 'end')
            ->where('funnel.stages.0.blocks.0.option_items.0.image_url', '/storage/funnels/opcao-a.png')
        );
});

test('public payload preserves horizontal option orientation', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Opcoes Horizontais',
        'slug' => 'funil-opcoes-horizontais',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [[
                    'id' => 'options-horizontal',
                    'type' => 'single_choice',
                    'label' => '',
                    'required' => false,
                    'options_layout' => 'grid_1',
                    'options_orientation' => 'horizontal',
                    'option_items' => [
                        ['id' => 'opt-a', 'label' => 'Opcao A', 'points' => 1, 'value' => 'A', 'destination' => 'next_stage'],
                        ['id' => 'opt-b', 'label' => 'Opcao B', 'points' => 2, 'value' => 'B', 'destination' => 'next_stage'],
                    ],
                ]],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.options_layout', 'grid_1')
            ->where('funnel.stages.0.blocks.0.options_orientation', 'horizontal')
        );
});

test('public payload includes testimonials price and level fields', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Componentes Avancados',
        'slug' => 'funil-componentes-avancados',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'testimonials-block',
                        'type' => 'testimonials',
                        'label' => '',
                        'required' => false,
                        'testimonials_layout' => 'grid',
                        'option_items' => [
                            [
                                'id' => 'testimonial-1',
                                'label' => 'Marina',
                                'subtitle' => '@marina',
                                'description' => 'Fechei meu primeiro funil em 3 dias.',
                                'rating' => 5,
                                'points' => 5,
                                'value' => '@marina',
                                'destination' => 'Fechei meu primeiro funil em 3 dias.',
                            ],
                        ],
                    ],
                    [
                        'id' => 'price-block',
                        'type' => 'price',
                        'label' => '',
                        'required' => false,
                        'price_title' => 'Plano Premium',
                        'price_prefix' => '12x de',
                        'price_value' => 'R$ 97',
                        'price_suffix' => 'sem juros',
                        'price_badge_text' => 'Mais vendido',
                        'price_mode' => 'redirect',
                        'price_layout' => 'horizontal',
                        'price_style' => 'theme',
                        'price_link' => 'https://example.com/checkout',
                    ],
                    [
                        'id' => 'level-block',
                        'type' => 'level',
                        'label' => '',
                        'required' => false,
                        'level_title' => 'Seu progresso',
                        'level_subtitle' => 'Subtitulo completo',
                        'level_percentage' => 72,
                        'level_indicator_text' => 'Voce esta aqui',
                        'level_legends' => 'Inicio, Meio, Final',
                        'level_show_meter' => true,
                        'level_show_progress' => true,
                        'level_type' => 'line',
                        'level_color' => 'green',
                    ],
                ],
            ],
        ],
    ]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.testimonials_layout', 'grid')
            ->where('funnel.stages.0.blocks.0.option_items.0.subtitle', '@marina')
            ->where('funnel.stages.0.blocks.0.option_items.0.rating', 5)
            ->where('funnel.stages.0.blocks.1.price_title', 'Plano Premium')
            ->where('funnel.stages.0.blocks.1.price_value', 'R$ 97')
            ->where('funnel.stages.0.blocks.1.price_mode', 'redirect')
            ->where('funnel.stages.0.blocks.1.price_link', 'https://example.com/checkout')
            ->where('funnel.stages.0.blocks.2.level_title', 'Seu progresso')
            ->where('funnel.stages.0.blocks.2.level_subtitle', 'Subtitulo completo')
            ->where('funnel.stages.0.blocks.2.level_percentage', 72)
            ->where('funnel.stages.0.blocks.2.level_indicator_text', 'Voce esta aqui')
            ->where('funnel.stages.0.blocks.2.level_legends', 'Inicio, Meio, Final')
            ->where('funnel.stages.0.blocks.2.level_show_meter', true)
            ->where('funnel.stages.0.blocks.2.level_show_progress', true)
            ->where('funnel.stages.0.blocks.2.level_type', 'line')
            ->where('funnel.stages.0.blocks.2.level_color', 'green')
        );
});

test('public submission stores lead and answers', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->post(route('funnels.public.submit', $funnel->slug), [
        'answers' => [
            [
                'stage_id' => $stages[0]->id,
                'blocks' => [
                    ['block_id' => 'name_block', 'value' => 'Thalysson'],
                    ['block_id' => 'email_block', 'value' => 'thalysson@example.com'],
                ],
            ],
            [
                'stage_id' => $stages[1]->id,
                'blocks' => [
                    ['block_id' => 'phone_block', 'value' => '+5511999999999'],
                    ['block_id' => 'challenge_block', 'value' => 'Leads'],
                ],
            ],
        ],
    ])->assertRedirect();

    $submission = FunnelSubmission::query()->where('funnel_id', $funnel->id)->first();

    expect($submission)->not->toBeNull();
    expect($submission?->lead_email)->toBe('thalysson@example.com');
    expect($submission?->answers()->count())->toBe(4);
    expect(data_get($submission?->meta, 'timeline.0.title'))->toBe('Lead recebido');
    Queue::assertPushed(ProcessFunnelSubmissionJob::class, 1);
});

test('public submission requires options blocks when options selection is required', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil obrigatorio',
        'slug' => 'funil-obrigatorio',
        'is_active' => true,
    ]);

    $stage = $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [[
                    'id' => 'consent_block',
                    'type' => 'yes_no',
                    'label' => 'Aceita continuar?',
                    'required' => false,
                    'options_required_selection' => true,
                    'option_items' => [
                        ['id' => 'yes', 'label' => 'Sim', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                        ['id' => 'no', 'label' => 'Nao', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                    ],
                ]],
            ],
        ],
    ]);

    $this->from(route('funnels.public.show', $funnel->slug))
        ->post(route('funnels.public.submit', $funnel->slug), [
            'answers' => [
                [
                    'stage_id' => $stage->id,
                    'blocks' => [
                        ['block_id' => 'consent_block', 'value' => null],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.public.show', $funnel->slug))
        ->assertSessionHasErrors('answers');

    expect(FunnelSubmission::query()->where('funnel_id', $funnel->id)->exists())->toBeFalse();
    Queue::assertNothingPushed();
});

test('public submission stores multiple choice arrays and ignores hidden conditional required blocks', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil multipla escolha',
        'slug' => 'funil-multipla-escolha',
        'is_active' => true,
    ]);

    $stage = $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'interest_block',
                        'type' => 'multiple_choice',
                        'label' => 'Interesses',
                        'required' => false,
                        'options_required_selection' => true,
                        'options_allow_multiple' => true,
                        'option_items' => [
                            ['id' => 'opt-1', 'label' => 'Leads', 'points' => 2, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'opt-2', 'label' => 'Vendas', 'points' => 5, 'value' => 'B', 'destination' => 'next_stage'],
                            ['id' => 'opt-3', 'label' => 'Outro', 'points' => 100, 'value' => 'C', 'destination' => 'next_stage'],
                        ],
                    ],
                    [
                        'id' => 'other_details_block',
                        'type' => 'text',
                        'label' => 'Detalhe outro',
                        'placeholder' => 'Explique',
                        'required' => true,
                        'display_rules' => [[
                            'id' => 'rule-1',
                            'source_block_id' => 'interest_block',
                            'operator' => 'contains_any',
                            'value' => 'Outro',
                        ]],
                    ],
                ],
            ],
        ],
    ]);

    $this->post(route('funnels.public.submit', $funnel->slug), [
        'answers' => [
            [
                'stage_id' => $stage->id,
                'blocks' => [
                    ['block_id' => 'interest_block', 'value' => ['Leads', 'Vendas']],
                ],
            ],
        ],
    ])->assertRedirect();

    $submission = FunnelSubmission::query()
        ->where('funnel_id', $funnel->id)
        ->latest('id')
        ->first();

    expect($submission)->not->toBeNull();
    expect($submission?->answers()->count())->toBe(1);
    expect($submission?->answers()->firstWhere('block_id', 'interest_block')?->value)->toBe(['Leads', 'Vendas']);
    expect(data_get($submission?->meta, 'score'))->toBe(7);
    expect(data_get($submission?->meta, 'score_breakdown.interest_block'))->toBe(7);
    Queue::assertPushed(ProcessFunnelSubmissionJob::class, 1);
});

test('public submission ignores grouped contains all rules when the block stays hidden', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil contains all',
        'slug' => 'funil-contains-all',
        'is_active' => true,
    ]);

    $stage = $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'interest_block',
                        'type' => 'multiple_choice',
                        'label' => 'Interesses',
                        'required' => false,
                        'options_required_selection' => true,
                        'options_allow_multiple' => true,
                        'option_items' => [
                            ['id' => 'opt-1', 'label' => 'Leads', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'opt-2', 'label' => 'Vendas', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                            ['id' => 'opt-3', 'label' => 'Outro', 'points' => 0, 'value' => 'C', 'destination' => 'next_stage'],
                        ],
                    ],
                    [
                        'id' => 'name_block',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => 'Seu nome',
                        'required' => true,
                    ],
                    [
                        'id' => 'conditional_block',
                        'type' => 'textarea',
                        'label' => 'Detalhes avancados',
                        'placeholder' => 'Conte mais',
                        'required' => true,
                        'display_rule_groups' => [[
                            'id' => 'group-1',
                            'mode' => 'all',
                            'rules' => [
                                [
                                    'id' => 'rule-1',
                                    'source_block_id' => 'interest_block',
                                    'operator' => 'contains_all',
                                    'value' => 'Leads|Outro',
                                ],
                                [
                                    'id' => 'rule-2',
                                    'source_block_id' => 'name_block',
                                    'operator' => 'filled',
                                    'value' => '',
                                ],
                            ],
                        ]],
                    ],
                ],
            ],
        ],
    ]);

    $this->post(route('funnels.public.submit', $funnel->slug), [
        'answers' => [
            [
                'stage_id' => $stage->id,
                'blocks' => [
                    ['block_id' => 'interest_block', 'value' => ['Leads']],
                    ['block_id' => 'name_block', 'value' => 'Lucas'],
                ],
            ],
        ],
    ])->assertRedirect();

    $submission = FunnelSubmission::query()
        ->where('funnel_id', $funnel->id)
        ->latest('id')
        ->first();

    expect($submission)->not->toBeNull();
    expect($submission?->answers()->count())->toBe(2);
    expect($submission?->answers()->firstWhere('block_id', 'interest_block')?->value)->toBe(['Leads']);
    expect($submission?->answers()->firstWhere('block_id', 'name_block')?->value)->toBe(['Lucas']);
    expect($submission?->answers()->firstWhere('block_id', 'conditional_block'))->toBeNull();
    Queue::assertPushed(ProcessFunnelSubmissionJob::class, 1);
});

test('custom domain submit stores lead using host based route', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->update(['custom_domain' => 'submit.cliente.com']);
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->post('http://submit.cliente.com/submit', [
            'answers' => [
                [
                    'stage_id' => $stages[0]->id,
                    'blocks' => [
                        ['block_id' => 'name_block', 'value' => 'Cliente Dominio'],
                        ['block_id' => 'email_block', 'value' => 'cliente@dominio.com'],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $submission = FunnelSubmission::query()->where('funnel_id', $funnel->id)->latest('id')->first();

    expect($submission)->not->toBeNull();
    expect($submission?->lead_email)->toBe('cliente@dominio.com');
    Queue::assertPushed(ProcessFunnelSubmissionJob::class, 1);
});

test('owner can list and filter leads', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Maria Teste',
        'lead_email' => 'maria@example.com',
        'status' => 'new',
        'meta' => [
            'tags' => ['vip'],
            'notes' => 'Lead quente',
            'timeline' => [[
                'type' => 'received',
                'source' => 'public_funnel',
                'actor_name' => 'Sistema',
                'title' => 'Lead recebido',
                'description' => 'Envio inicial do funil.',
                'created_at' => now()->toISOString(),
            ]],
        ],
    ]);
    FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Joao Teste',
        'lead_email' => 'joao@example.com',
        'status' => 'contacted',
    ]);

    $this->actingAs($owner)
        ->get(route('leads.index', ['q' => 'Maria', 'status' => 'new', 'funnel_id' => $funnel->id, 'tag' => 'vip']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.lead_name', 'Maria Teste')
            ->where('leads.data.0.tags.0', 'vip')
            ->where('leads.data.0.notes', 'Lead quente')
            ->where('leads.data.0.timeline.0.actor_name', 'Sistema')
            ->has('leads.data.0.timeline')
        );
});

test('owner can view lead detail page with answers and timeline', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $lead = FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Lead Detalhado',
        'meta' => [
            'score' => 12,
            'tags' => ['vip'],
            'notes' => 'Contato prioritario',
            'timeline' => [[
                'id' => 'event-1',
                'type' => 'submitted',
                'source' => 'public_funnel',
                'actor_name' => 'Sistema',
                'title' => 'Lead recebido',
                'description' => 'Envio inicial do funil.',
                'created_at' => now()->toISOString(),
            ]],
        ],
    ]);
    $lead->answers()->create([
        'funnel_stage_id' => $funnel->stages()->firstOrFail()->id,
        'block_id' => 'name_block',
        'block_type' => 'text',
        'block_label' => 'Nome',
        'value' => ['Lead Detalhado'],
    ]);

    $this->actingAs($owner)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leads/Show')
            ->where('lead.id', $lead->id)
            ->where('lead.score', 12)
            ->where('lead.answers.0.block_label', 'Nome')
            ->where('lead.timeline.0.title', 'Lead recebido')
            ->where('lead.tags.0', 'vip')
        );
});

test('owner can update lead crm fields and filter by them', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->sharedUsers()->attach($editor->id, [
        'role' => Funnel::SHARE_ROLE_EDITOR,
        'shared_by_user_id' => $owner->id,
    ]);

    $lead = FunnelSubmission::factory()->for($funnel)->create([
        'status' => 'new',
        'meta' => [
            'tags' => ['vip'],
            'notes' => 'Precisa de retorno',
            'timeline' => [],
        ],
    ]);

    $this->actingAs($owner)
        ->patch(route('leads.update', $lead), [
            'status' => 'contacted',
            'assignee_id' => $editor->id,
            'priority' => 'urgent',
            'next_follow_up_at' => '2026-03-12T14:30',
            'tags' => ['vip', 'premium'],
            'notes' => 'Contato avancado',
        ])
        ->assertRedirect();

    $lead->refresh();

    expect($lead->status)->toBe('contacted');
    expect(data_get($lead->meta, 'assignee_id'))->toBe($editor->id);
    expect(data_get($lead->meta, 'assignee_name'))->toBe($editor->name);
    expect(data_get($lead->meta, 'priority'))->toBe('urgent');
    expect(data_get($lead->meta, 'next_follow_up_at'))->toContain('2026-03-12T14:30');
    expect(data_get($lead->meta, 'timeline.0.title'))->toBe('Lead atualizado');

    $this->actingAs($owner)
        ->get(route('leads.index', [
            'assignee_id' => (string) $editor->id,
            'priority' => 'urgent',
            'has_notes' => 'yes',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.assignee.id', $editor->id)
            ->where('leads.data.0.priority', 'urgent')
            ->where('leads.data.0.next_follow_up_at', data_get($lead->meta, 'next_follow_up_at'))
            ->where('leads.data.0.timeline.0.metadata', [])
        );
});

test('owner can update lead details with tags and notes', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $lead = FunnelSubmission::factory()->for($funnel)->create([
        'status' => 'new',
        'meta' => ['tags' => ['frio'], 'notes' => ''],
    ]);

    $this->actingAs($owner)
        ->patch(route('leads.update', $lead), [
            'status' => 'contacted',
            'tags' => ['vip', 'whatsapp'],
            'notes' => 'Ligacao agendada',
        ])
        ->assertRedirect();

    $lead->refresh();

    expect($lead->status)->toBe('contacted');
    expect(data_get($lead->meta, 'tags'))->toBe(['vip', 'whatsapp']);
    expect(data_get($lead->meta, 'notes'))->toBe('Ligacao agendada');
    expect(data_get($lead->meta, 'last_contacted_at'))->not->toBeNull();
    expect(data_get($lead->meta, 'timeline.0.title'))->toBe('Lead atualizado');
    expect(data_get($lead->meta, 'timeline.0.actor_name'))->toBe($owner->name);
    expect(data_get($lead->meta, 'timeline.0.source'))->toBe('panel');
});

test('owner can update lead status', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $lead = FunnelSubmission::factory()->for($funnel)->create([
        'status' => 'new',
    ]);

    $this->actingAs($owner)
        ->patch(route('leads.status', $lead), ['status' => 'qualified'])
        ->assertRedirect();

    expect($lead->fresh()->status)->toBe('qualified');
    expect(data_get($lead->fresh()->meta, 'timeline.0.title'))->toBe('Status alterado');
});

test('shared viewer cannot update or view lead details', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);
    $lead = FunnelSubmission::factory()->for($funnel)->create([
        'status' => 'new',
    ]);

    $this->actingAs($viewer)
        ->get(route('leads.show', $lead))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('leads.update', $lead), [
            'status' => 'contacted',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('leads.status', $lead), [
            'status' => 'qualified',
        ])
        ->assertForbidden();
});

test('process funnel submission job appends processed timeline event', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $submission = FunnelSubmission::factory()->for($funnel)->create([
        'meta' => [
            'timeline' => [[
                'id' => 'existing',
                'type' => 'submitted',
                'source' => 'public_funnel',
                'actor_name' => 'Sistema',
                'title' => 'Lead recebido',
                'description' => 'Envio inicial do funil.',
                'created_at' => now()->subMinute()->toISOString(),
            ]],
        ],
    ]);

    $submission->answers()->create([
        'funnel_stage_id' => $funnel->stages()->firstOrFail()->id,
        'block_id' => 'name_block',
        'block_type' => 'text',
        'block_label' => 'Nome',
        'value' => ['Maria'],
    ]);

    (new ProcessFunnelSubmissionJob($submission->id))->handle();

    $submission->refresh();

    expect(data_get($submission->meta, 'timeline.0.title'))->toBe('Lead processado');
    expect(data_get($submission->meta, 'timeline.0.source'))->toBe('queue');
    expect(data_get($submission->meta, 'answers_count'))->toBe(1);
});

test('owner can export leads csv', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Export Lead',
        'status' => 'new',
        'meta' => ['score' => 9, 'tags' => ['vip', 'campanha'], 'notes' => 'Observacao interna'],
    ]);

    $response = $this->actingAs($owner)->get(route('leads.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Export Lead');
    expect($response->streamedContent())->toContain('vip, campanha');
    expect($response->streamedContent())->toContain('Observacao interna');
    expect($response->streamedContent())->toContain('Pontuacao');
    expect($response->streamedContent())->toContain(',9,');
});

test('shared editor can list leads of shared funnel', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->sharedUsers()->attach($editor->id, [
        'role' => Funnel::SHARE_ROLE_EDITOR,
    ]);
    FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Lead Compartilhado',
    ]);

    $this->actingAs($editor)
        ->get(route('leads.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.lead_name', 'Lead Compartilhado')
        );
});

test('shared viewer does not see leads from shared funnel', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);
    FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Lead Restrito',
    ]);

    $this->actingAs($viewer)
        ->get(route('leads.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leads/Index')
            ->has('leads.data', 0)
        );
});

test('backup leads command creates csv snapshot file', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    FunnelSubmission::factory()->for($funnel)->create([
        'lead_name' => 'Backup Lead',
    ]);

    $this->artisan('app:backup-leads')
        ->assertSuccessful();

    $files = Storage::disk('local')->files('backups/leads');

    expect($files)->not->toBeEmpty();

    $content = Storage::disk('local')->get($files[0]);
    expect($content)->toContain('Backup Lead');
});

test('public payload includes notification avatar size and variant', function () {
    $owner = User::factory()->create();
    $funnel = makePublicFunnel($owner);
    $firstStage = $funnel->stages()->orderBy('stage_order')->firstOrFail();
    $meta = is_array($firstStage->meta) ? $firstStage->meta : [];
    $builder = is_array($meta['builder'] ?? null) ? $meta['builder'] : [];

    $builder['blocks'] = [
        [
            'id' => 'notification_block',
            'type' => 'notification',
            'label' => '',
            'notification_title' => '@1 entrou no grupo VIP',
            'notification_description' => 'Ultimo canal: @2',
            'notification_avatar_url' => '/media/funnels/1/media/image/avatar.png',
            'notification_position' => 'top_center',
            'notification_duration_seconds' => 5,
            'notification_interval_seconds' => 3,
            'notification_style' => 'white',
            'notification_size' => 'large',
            'notification_variant' => 'message',
            'notification_variations' => [
                ['id' => 'var_1', 'value1' => 'Livia', 'value2' => 'Whatsapp', 'value3' => '1'],
            ],
        ],
    ];

    $meta['builder'] = $builder;
    $firstStage->update(['meta' => $meta]);

    $this->get(route('funnels.public.show', $funnel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/PublicShow')
            ->where('funnel.stages.0.blocks.0.notification_avatar_url', '/media/funnels/1/media/image/avatar.png')
            ->where('funnel.stages.0.blocks.0.notification_size', 'large')
            ->where('funnel.stages.0.blocks.0.notification_variant', 'message')
            ->where('funnel.stages.0.blocks.0.notification_position', 'top_center')
        );
});
