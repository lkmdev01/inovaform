<?php

use App\Models\Funnel;
use App\Models\FunnelTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $databasePath = database_path('browser-testing-'.uniqid('', true).'.sqlite');

    touch($databasePath);

    putenv('DB_CONNECTION=sqlite');
    putenv("DB_DATABASE={$databasePath}");
    putenv('SESSION_DRIVER=file');
    putenv('CACHE_STORE=file');

    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', $databasePath);
    config()->set('session.driver', 'file');
    config()->set('cache.default', 'file');

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Artisan::call('migrate:fresh', [
        '--force' => true,
    ]);
});

function loginThroughBrowser(User $user): mixed
{
    return visit('/login')
        ->assertSee('Acessar conta')
        ->type('#email', $user->email)
        ->type('#password', 'password')
        ->press('Entrar no painel')
        ->wait(1);
}

function createEmptyStagePayload(): array
{
    return [
        'builder' => [
            'title' => '',
            'subtitle' => '',
            'button_text' => '',
            'blocks' => [],
        ],
    ];
}

function clickPreviewCanvas(mixed $page): mixed
{
    $page->script(<<<'JS'
        () => {
            const canvas = document.querySelector('[data-testid="builder-preview-canvas"]');

            if (!canvas) {
                throw new Error('Canvas do preview nao encontrado');
            }

            canvas.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        }
    JS);

    return $page;
}

test('builder exposes a separate action to save the current funnel as a template', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil criado com IA',
    ]);
    $funnel->stages()->createMany([
        ['name' => 'Etapa 1', 'stage_order' => 1, 'meta' => createEmptyStagePayload()],
        ['name' => 'Etapa 2', 'stage_order' => 2, 'meta' => createEmptyStagePayload()],
    ]);

    loginThroughBrowser($user)
        ->navigate("/funnels/{$funnel->id}/builder")
        ->click('[data-testid="builder-save-template-button"]')
        ->assertVisible('[data-testid="builder-save-template-dialog"]')
        ->type('[data-testid="builder-template-name"]', 'Modelo de qualificação')
        ->click('[data-testid="builder-confirm-save-template"]')
        ->wait(1)
        ->assertSee('Template salvo na sua biblioteca.')
        ->assertNoJavaScriptErrors();

    expect(FunnelTemplate::query()->whereBelongsTo($user)->count())->toBe(1);
});

test('builder explains the strategy and quality audit of an ai generated funnel', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Diagnóstico inteligente',
        'design_settings' => [
            'aiGeneration' => [
                'objective_summary' => 'Qualificar empresas e entregar um diagnóstico inicial.',
                'rationale' => 'A jornada começa pelo contexto e captura o contato antes do resultado.',
                'stage_plan' => [
                    ['name' => 'Contexto', 'purpose' => 'Entender o perfil da empresa.'],
                    ['name' => 'Resultado', 'purpose' => 'Apresentar o próximo passo.'],
                ],
                'quality_score' => 100,
                'quality_notes' => [],
                'correction_applied' => true,
            ],
        ],
    ]);
    $funnel->stages()->createMany([
        ['name' => 'Contexto', 'stage_order' => 1],
        ['name' => 'Resultado', 'stage_order' => 2],
    ]);

    loginThroughBrowser($user)
        ->navigate("/funnels/{$funnel->id}/builder")
        ->click('[data-testid="builder-ai-strategy"] summary')
        ->assertSee('Qualificar empresas e entregar um diagnóstico inicial.')
        ->assertSee('100/100')
        ->assertSee('revisão automática')
        ->assertNoJavaScriptErrors();
});

test('builder exposes focused mobile panels and preserves the desktop workspace', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder responsivo',
    ]);
    $funnel->stages()->create([
        'name' => 'Etapa inicial',
        'stage_order' => 1,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->navigate("/funnels/{$funnel->id}/builder")
        ->resize(390, 844)
        ->assertVisible('[data-testid="builder-mobile-panel-nav"]')
        ->assertVisible('[data-testid="builder-preview-card"]')
        ->click('[data-testid="builder-mobile-panel-library"]')
        ->assertVisible('[data-testid="palette-block-text"]')
        ->click('[data-testid="palette-block-text"]')
        ->assertVisible('[data-testid="builder-component-tab-component"]')
        ->assertNoJavaScriptErrors();

    expect($page->script('() => document.body.scrollWidth <= document.documentElement.clientWidth'))->toBeTrue();

    $page->resize(1440, 900)
        ->assertVisible('[data-testid="builder-stage-item-1"]')
        ->assertVisible('[data-testid="palette-block-text"]')
        ->assertVisible('[data-testid="builder-preview-card"]')
        ->assertVisible('[data-testid="builder-component-tab-component"]')
        ->assertNoJavaScriptErrors();
});

test('builder block selection toggles the side panel tabs', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Browser',
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => 'Subtitulo',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'block-text',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => 'Digite seu nome',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertVisible('[data-testid="builder-step-tab"]')
        ->assertNotPresent('[data-testid="builder-component-tab-component"]')
        ->click('[data-testid="preview-block-block-text"]')
        ->assertVisible('[data-testid="builder-component-tab-component"]')
        ->assertSee('ID/Name');

    clickPreviewCanvas($page)
        ->assertVisible('[data-testid="builder-step-tab"]')
        ->assertSee('Titulo, subtitulo e CTA agora sao blocos.')
        ->assertNotPresent('[data-testid="builder-component-tab-component"]')
        ->assertNoJavaScriptErrors();
});

test('builder stage button selection behaves like a component panel selection', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Browser Button',
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => 'Subtitulo',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'button-block',
                        'type' => 'button',
                        'label' => 'Continuar',
                        'required' => false,
                        'button_action' => 'next_stage',
                        'button_target_stage_order' => 'next',
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertVisible('[data-testid="builder-step-tab"]')
        ->click('[data-testid="preview-block-button-block"]')
        ->assertVisible('[data-testid="builder-component-tab-component"]')
        ->assertSee('Tipo de navegacao');

    clickPreviewCanvas($page)
        ->assertVisible('[data-testid="builder-step-tab"]')
        ->assertNotPresent('[data-testid="builder-component-tab-component"]')
        ->assertNoJavaScriptErrors();
});

test('builder can add a block by dragging from the palette into the preview', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Drag Drop',
    ]);

    $funnel->stages()->create([
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
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertVisible('[data-testid="builder-preview-canvas"]');

    expect($page->script("() => document.querySelectorAll('[data-testid^=\"preview-block-\"]').length"))->toBe(0);

    $page->drag('[data-testid="palette-block-text"]', '[data-testid="builder-preview-canvas"]')
        ->wait(0.5)
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelectorAll('[data-testid^=\"preview-block-\"]').length"))->toBe(1);
});

test('builder rich text editor updates preview content and persists after save', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Content Text',
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
                        'id' => 'block-content',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<h2>Texto inicial</h2><p>Paragrafo inicial</p>',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-content"]')
        ->assertVisible('[data-testid="content-text-editor"]');

    $page->script(<<<'JS'
        () => {
            const editor = document.querySelector('[data-testid="content-text-editor"]');

            if (! editor) {
                throw new Error('Editor nao encontrado');
            }

            editor.innerHTML = '<h2>Novo conteudo rico</h2><p>Paragrafo atualizado</p>';
            editor.dispatchEvent(new Event('input', { bubbles: true }));
            editor.dispatchEvent(new Event('blur', { bubbles: true }));
        }
    JS);

    $page->assertSee('Novo conteudo rico')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $savedStage = $stage->fresh();
    $savedMarkup = (string) data_get($savedStage->meta, 'builder.blocks.0.placeholder', '');

    expect($savedMarkup)->toContain('Novo conteudo rico');
});

test('builder content text preview respects configured text alignment', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Content Text Alignment',
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
                        'id' => 'block-content-aligned',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<h2>Conteudo alinhado</h2><p>Descricao</p>',
                        'text_align' => 'text-right',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('Conteudo alinhado')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"content-text-preview-block-content-aligned\"]')?.className.includes('text-right')"))->toBeTrue();
});

test('builder centers attention text and rotates notification variations inside the floating preview', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Notification Preview',
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
                        'id' => 'attention-block',
                        'type' => 'attention',
                        'label' => '',
                        'placeholder' => 'Texto importante',
                        'required' => false,
                        'attention_style' => 'red',
                    ],
                    [
                        'id' => 'notification-block',
                        'type' => 'notification',
                        'label' => '',
                        'required' => false,
                        'notification_title' => '@1 comprou agora',
                        'notification_description' => 'Origem: @2. Restam @3 vagas.',
                        'notification_avatar_url' => '@4',
                        'notification_position' => 'bottom_left',
                        'notification_interval_seconds' => 1,
                        'notification_variations' => [
                            [
                                'id' => 'variation-1',
                                'value1' => 'Joao',
                                'value2' => 'Instagram',
                                'value3' => '3',
                                'value4' => 'https://cdn.example.com/avatar-joao.png',
                            ],
                            [
                                'id' => 'variation-2',
                                'value1' => 'Maria',
                                'value2' => 'WhatsApp',
                                'value3' => '2',
                                'value4' => 'https://cdn.example.com/avatar-maria.png',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('Texto importante')
        ->assertVisible('[data-testid="builder-notification-notification-block"]');

    expect($page->script("() => document.querySelector('[data-testid=\"preview-block-attention-block\"] > div')?.className.includes('text-center')"))->toBeTrue();
    expect($page->script("() => document.querySelector('[data-testid=\"builder-notification-preview-frame-notification-block\"]') !== null"))->toBeTrue();

    $initialNotificationText = $page->script("() => document.querySelector('[data-testid=\"builder-notification-notification-block\"]')?.textContent ?? ''");
    $initialAvatarUrl = $page->script("() => document.querySelector('[data-testid=\"builder-notification-notification-block\"] img[alt=\"Avatar da notificacao\"]')?.getAttribute('src') ?? ''");

    $page->wait(1.2)
        ->assertNoJavaScriptErrors();

    $rotatedNotificationText = $page->script("() => document.querySelector('[data-testid=\"builder-notification-notification-block\"]')?.textContent ?? ''");
    $rotatedAvatarUrl = $page->script("() => document.querySelector('[data-testid=\"builder-notification-notification-block\"] img[alt=\"Avatar da notificacao\"]')?.getAttribute('src') ?? ''");

    expect($initialNotificationText)->toContain('Joao');
    expect($rotatedNotificationText)->toContain('Maria');
    expect($initialAvatarUrl)->toBe('https://cdn.example.com/avatar-joao.png');
    expect($rotatedAvatarUrl)->toBe('https://cdn.example.com/avatar-maria.png');
});

test('builder keeps loading, level and price previews aligned with the public funnel', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Component Parity',
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
                        'id' => 'loading-block',
                        'type' => 'loading',
                        'label' => 'Processando sua resposta',
                        'placeholder' => 'Aguarde alguns segundos',
                        'required' => false,
                        'loading_start_seconds' => 35,
                        'loading_duration_seconds' => 5,
                        'loading_show_title' => true,
                        'loading_show_progress' => true,
                    ],
                    [
                        'id' => 'level-block',
                        'type' => 'level',
                        'label' => '',
                        'required' => false,
                        'level_title' => 'Seu progresso',
                        'level_subtitle' => 'Texto longo para garantir que o preview do builder nao trunque o subtitulo.',
                        'level_percentage' => 68,
                        'level_indicator_text' => 'Voce esta aqui',
                        'level_legends' => 'Inicio, Meio, Final',
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
                        'price_link' => 'https://example.com/checkout',
                        'price_layout' => 'horizontal',
                        'price_style' => 'theme',
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('Processando sua resposta')
        ->assertSee('Seu progresso')
        ->assertSee('Plano Premium')
        ->assertNoJavaScriptErrors();

    expect($page->script("
        () => {
            const block = document.querySelector('[data-testid=\"preview-block-loading-block\"]');

            if (!block) {
                return false;
            }

            return Array.from(block.querySelectorAll('div')).some((element) => element.className.includes('bg-[#e3e6ed]'));
        }
    "))->toBeTrue();
    expect($page->script("
        () => {
            const block = document.querySelector('[data-testid=\"preview-block-loading-block\"]');

            if (!block) {
                return false;
            }

            return Array.from(block.querySelectorAll('div'))
                .some((element) => element.className.includes('transition-all') && (element.getAttribute('style') ?? '').includes('35%'));
        }
    "))->toBeTrue();
    expect($page->script("() => document.querySelector('[data-testid=\"preview-block-loading-block\"]')?.textContent?.includes('35%') ?? false"))->toBeTrue();
    expect($page->script("() => document.querySelector('[data-testid=\"builder-level-subtitle-level-block\"]')?.className.includes('truncate') ?? false"))->toBeFalse();
    expect($page->script("
        () => {
            const block = document.querySelector('[data-testid=\"preview-block-price-block\"]');

            if (!block) {
                return null;
            }

            const clickable = Array.from(block.querySelectorAll('div'))
                .find((element) => getComputedStyle(element).cursor === 'pointer');

            return clickable ? getComputedStyle(clickable).cursor : null;
        }
    "))->toBe('pointer');
    expect($page->script("() => document.querySelector('[data-testid=\"preview-block-price-block\"]')?.textContent?.includes('Mais vendido') ?? false"))->toBeTrue();
});

test('builder keeps testimonials, faq, carousel and metrics previews aligned with the public funnel', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Argument Preview Parity',
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
                        'id' => 'block-testimonials-preview',
                        'type' => 'testimonials',
                        'label' => '',
                        'required' => false,
                        'testimonials_layout' => 'grid',
                        'option_items' => [[
                            'id' => 'testimonial-preview-item',
                            'label' => '',
                            'subtitle' => '@joao',
                            'description' => 'Fechou em 7 dias.',
                            'rating' => 5,
                        ]],
                    ],
                    [
                        'id' => 'block-faq-preview',
                        'type' => 'faq',
                        'label' => '',
                        'required' => false,
                        'faq_first_active' => true,
                        'faq_detail' => 'plus_minus',
                        'option_items' => [
                            [
                                'id' => 'faq-preview-1',
                                'label' => 'Como funciona?',
                                'description' => 'Voce responde e recebe o diagnostico.',
                            ],
                            [
                                'id' => 'faq-preview-2',
                                'label' => 'Tem custo?',
                                'description' => 'Nao.',
                            ],
                        ],
                    ],
                    [
                        'id' => 'block-carousel-preview',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'carousel_layout' => 'text_only',
                        'carousel_pagination' => true,
                        'option_items' => [[
                            'id' => 'carousel-preview-item',
                            'label' => '',
                            'value' => '',
                            'description' => 'Slide sem imagem',
                        ]],
                    ],
                    [
                        'id' => 'block-metrics-preview',
                        'type' => 'metrics',
                        'label' => '',
                        'required' => false,
                        'option_items' => [
                            [
                                'id' => 'metric-empty',
                                'label' => '',
                                'value' => '',
                                'description' => '',
                            ],
                            [
                                'id' => 'metric-filled',
                                'label' => '',
                                'value' => '',
                                'description' => 'Mais de 500 respostas',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('@joao')
        ->assertSee('Fechou em 7 dias.')
        ->assertSee('Voce responde e recebe o diagnostico.')
        ->assertSee('Slide sem imagem')
        ->assertSee('Mais de 500 respostas');

    expect($page->script(<<<'JS'
        () => {
            const block = document.querySelector('[data-testid="preview-block-block-testimonials-preview"]');

            if (!(block instanceof HTMLElement)) {
                throw new Error('Preview de depoimentos nao encontrado');
            }

            return block.querySelectorAll('p.mt-0\\.5.text-base.font-semibold.text-white').length;
        }
    JS))->toBe(0);

    expect($page->script(<<<'JS'
        () => {
            const block = document.querySelector('[data-testid="preview-block-block-faq-preview"]');

            if (!(block instanceof HTMLElement)) {
                throw new Error('Preview de FAQ nao encontrado');
            }

            return {
                firstAnswerVisible: block.textContent.includes('Voce responde e recebe o diagnostico.'),
                secondAnswerVisible: block.textContent.includes('Nao.'),
            };
        }
    JS))->toBe([
        'firstAnswerVisible' => true,
        'secondAnswerVisible' => false,
    ]);

    expect($page->script(<<<'JS'
        () => {
            const block = document.querySelector('[data-testid="preview-block-block-carousel-preview"]');

            if (!(block instanceof HTMLElement)) {
                throw new Error('Preview de carousel nao encontrado');
            }

            return {
                hasImage: block.querySelector('img[alt="Imagem do slide"]') !== null,
                hasDescription: block.textContent.includes('Slide sem imagem'),
            };
        }
    JS))->toBe([
        'hasImage' => false,
        'hasDescription' => true,
    ]);

    expect($page->script(<<<'JS'
        () => {
            const block = document.querySelector('[data-testid="preview-block-block-metrics-preview"]');

            if (!(block instanceof HTMLElement)) {
                throw new Error('Preview de metricas nao encontrado');
            }

            return block.querySelectorAll('.rounded-2xl.border.border-\\[\\#2a4e88\\].bg-\\[\\#0b274f\\].px-3.py-3').length;
        }
    JS))->toBe(1);

    $page->assertNoJavaScriptErrors();
});

test('builder accepts spaces while editing content components and persists the complete text', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Content Spaces',
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
                        'id' => 'testimonials-spaces-block',
                        'type' => 'testimonials',
                        'label' => '',
                        'required' => false,
                        'option_items' => [[
                            'id' => 'testimonial-spaces-item',
                            'label' => '',
                            'subtitle' => '',
                            'description' => '',
                            'rating' => 5,
                        ]],
                    ],
                    [
                        'id' => 'faq-spaces-block',
                        'type' => 'faq',
                        'label' => '',
                        'required' => false,
                        'option_items' => [[
                            'id' => 'faq-spaces-item',
                            'label' => '',
                            'description' => '',
                        ]],
                    ],
                    [
                        'id' => 'carousel-spaces-block',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'carousel_layout' => 'text_only',
                        'option_items' => [[
                            'id' => 'carousel-spaces-item',
                            'label' => '',
                            'value' => '',
                            'description' => '',
                        ]],
                    ],
                    [
                        'id' => 'metrics-spaces-block',
                        'type' => 'metrics',
                        'label' => '',
                        'required' => false,
                        'option_items' => [[
                            'id' => 'metric-spaces-item',
                            'label' => '',
                            'value' => '',
                            'description' => '',
                        ]],
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-testimonials-spaces-block"]')
        ->type('[data-testid^="builder-testimonial-name-"]', 'Maria Silva')
        ->type('[data-testid^="builder-testimonial-description-"]', 'Atendimento muito atencioso')
        ->assertValue('[data-testid^="builder-testimonial-name-"]', 'Maria Silva')
        ->assertValue('[data-testid^="builder-testimonial-description-"]', 'Atendimento muito atencioso')
        ->click('[data-testid="preview-block-faq-spaces-block"]')
        ->type('[data-testid^="option-item-label-"]', 'Como funciona agora')
        ->type('[data-testid^="builder-faq-answer-"]', 'Voce recebe uma resposta completa')
        ->assertValue('[data-testid^="option-item-label-"]', 'Como funciona agora')
        ->assertValue('[data-testid^="builder-faq-answer-"]', 'Voce recebe uma resposta completa')
        ->click('[data-testid="preview-block-carousel-spaces-block"]')
        ->type('[data-testid^="builder-carousel-description-"]', 'Uma historia com espacos')
        ->assertValue('[data-testid^="builder-carousel-description-"]', 'Uma historia com espacos')
        ->click('[data-testid="preview-block-metrics-spaces-block"]')
        ->type('[data-testid^="builder-metric-label-"]', 'Taxa de conversao')
        ->type('[data-testid^="builder-metric-value-"]', '50 por cento')
        ->type('[data-testid^="builder-metric-description-"]', 'Resultado medio mensal')
        ->assertValue('[data-testid^="builder-metric-label-"]', 'Taxa de conversao')
        ->assertValue('[data-testid^="builder-metric-value-"]', '50 por cento')
        ->assertValue('[data-testid^="builder-metric-description-"]', 'Resultado medio mensal')
        ->wait(3)
        ->assertSee('Salvo')
        ->assertNoJavaScriptErrors();

    $savedBlocks = collect(data_get($stage->fresh()->meta, 'builder.blocks', []))->keyBy('type');

    expect(data_get($savedBlocks, 'testimonials.option_items.0.label'))->toBe('Maria Silva')
        ->and(data_get($savedBlocks, 'testimonials.option_items.0.description'))->toBe('Atendimento muito atencioso')
        ->and(data_get($savedBlocks, 'faq.option_items.0.label'))->toBe('Como funciona agora')
        ->and(data_get($savedBlocks, 'faq.option_items.0.description'))->toBe('Voce recebe uma resposta completa')
        ->and(data_get($savedBlocks, 'carousel.option_items.0.description'))->toBe('Uma historia com espacos')
        ->and(data_get($savedBlocks, 'metrics.option_items.0.label'))->toBe('Taxa de conversao')
        ->and(data_get($savedBlocks, 'metrics.option_items.0.value'))->toBe('50 por cento')
        ->and(data_get($savedBlocks, 'metrics.option_items.0.description'))->toBe('Resultado medio mensal');
});

test('builder previews carousel autoplay with its configured speed', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Carousel Autoplay',
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
                    'id' => 'carousel-autoplay-block',
                    'type' => 'carousel',
                    'label' => '',
                    'required' => false,
                    'carousel_layout' => 'text_only',
                    'carousel_pagination' => true,
                    'carousel_autoplay' => false,
                    'carousel_autoplay_seconds' => 3,
                    'option_items' => [
                        ['id' => 'carousel-autoplay-1', 'label' => '', 'value' => '', 'description' => 'Primeiro item', 'points' => 0, 'destination' => ''],
                        ['id' => 'carousel-autoplay-2', 'label' => '', 'value' => '', 'description' => 'Segundo item', 'points' => 0, 'destination' => ''],
                    ],
                ]],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-carousel-autoplay-block"]')
        ->click('[data-testid="builder-carousel-autoplay"]')
        ->fill('[data-testid="builder-carousel-autoplay-seconds"]', '1')
        ->assertValue('[data-testid="builder-carousel-autoplay-seconds"]', '1');

    $initialItem = $page->script("() => document.querySelector('[data-testid=\"builder-carousel-current-carousel-autoplay-block\"]')?.textContent?.trim() ?? ''");

    $page->wait(1.2)->assertNoJavaScriptErrors();

    $nextItem = $page->script("() => document.querySelector('[data-testid=\"builder-carousel-current-carousel-autoplay-block\"]')?.textContent?.trim() ?? ''");

    expect($nextItem)->not->toBe($initialItem);
});

test('builder warns when notification variations are filled but title and description do not use tokens', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Notification Tokens Warning',
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
                        'id' => 'notification-warning-block',
                        'type' => 'notification',
                        'label' => '',
                        'required' => false,
                        'notification_title' => 'Joao',
                        'notification_description' => 'Comprou o curso.',
                        'notification_variations' => [
                            [
                                'id' => 'variation-warning-1',
                                'value1' => 'Oi',
                                'value2' => 'teste2',
                                'value3' => 'teste3',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-notification-warning-block"]')
        ->assertVisible('[data-testid="builder-component-tab-component"]')
        ->assertVisible('[data-testid="notification-variation-warning"]');

    $page->click('[data-testid="notification-token-title-1"]')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"notification-variation-warning\"]') === null"))->toBeTrue();
    expect($page->script("() => document.querySelector('input[placeholder=\"@1 acabou de comprar\"]')?.value ?? ''"))->toContain('@1');
});

test('builder seeds new notification blocks with tokenized defaults', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Notification Defaults',
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="palette-block-notification"]')
        ->assertVisible('[data-testid="builder-component-tab-component"]');

    expect($page->script("() => document.querySelector('input[placeholder=\"@1 acabou de comprar\"]')?.value ?? ''"))->toBe('@1');
    expect($page->script("() => document.querySelector('textarea[placeholder=\"Comprou pelo @2. Restam @3 vagas.\"]')?.value ?? ''"))->toContain('@2');
    expect($page->script("() => document.querySelector('textarea[placeholder=\"Comprou pelo @2. Restam @3 vagas.\"]')?.value ?? ''"))->toContain('@3');
    expect($page->script("() => document.querySelector('input[placeholder=\"@1\"]')?.value ?? ''"))->toBe('Joao');
    expect($page->script("() => document.querySelector('input[placeholder=\"@2\"]')?.value ?? ''"))->toBe('Instagram');
    expect($page->script("() => document.querySelector('input[placeholder=\"@3\"]')?.value ?? ''"))->toBe('3');
    expect($page->script("() => document.querySelector('input[placeholder=\"@4\"]')?.value ?? ''"))->toBe('https://cdn.example.com/avatar-joao.png');
});

test('builder audio preview supports rapid play pause and seek interactions', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Audio Preview',
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
                    'id' => 'audio-preview',
                    'type' => 'audio',
                    'label' => '',
                    'required' => false,
                    'audio_src' => 'https://example.com/audio.mp3',
                    'audio_sender' => 'Equipe',
                    'audio_theme' => 'light',
                ]],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('Equipe');

    $page->script(<<<'JS'
        () => {
            const audio = document.querySelector('audio');

            if (! audio) {
                throw new Error('Audio nao encontrado');
            }

            let currentTime = 0;
            let paused = true;
            let playCount = 0;
            let pauseCount = 0;

            Object.defineProperty(audio, 'duration', {
                configurable: true,
                get: () => 120,
            });

            Object.defineProperty(audio, 'currentTime', {
                configurable: true,
                get: () => currentTime,
                set: (value) => { currentTime = Number(value); },
            });

            Object.defineProperty(audio, 'paused', {
                configurable: true,
                get: () => paused,
            });

            audio.play = async () => {
                paused = false;
                playCount += 1;
                window.__builderAudioPlayCount = playCount;
                audio.dispatchEvent(new Event('play'));
            };

            audio.pause = () => {
                paused = true;
                pauseCount += 1;
                window.__builderAudioPauseCount = pauseCount;
                audio.dispatchEvent(new Event('pause'));
            };

            audio.dispatchEvent(new Event('loadedmetadata'));
        }
    JS);

    $page->script(<<<'JS'
        () => {
            const toggle = document.querySelector('[data-testid="builder-audio-toggle-audio-preview"]');

            if (! toggle) {
                throw new Error('Toggle do builder nao encontrado');
            }

            toggle.click();
            toggle.click();
        }
    JS);

    $page->script(<<<'JS'
        () => {
            const seek = document.querySelector('[data-testid="builder-audio-seek-audio-preview"]');

            if (! seek) {
                throw new Error('Seek do builder nao encontrado');
            }

            const rect = seek.getBoundingClientRect();
            seek.dispatchEvent(new MouseEvent('click', {
                bubbles: true,
                clientX: rect.left + (rect.width / 2),
                clientY: rect.top + (rect.height / 2),
            }));
        }
    JS);

    $page->script(<<<'JS'
        () => {
            const toggle = document.querySelector('[data-testid="builder-audio-toggle-audio-preview"]');

            if (! toggle) {
                throw new Error('Toggle do builder nao encontrado');
            }

            toggle.click();
        }
    JS);

    $page->wait(0.5)
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"builder-audio-current-audio-preview\"]')?.textContent?.trim() ?? ''"))->toBeIn(['00:59', '01:00']);
    expect($page->script("() => document.querySelector('[data-testid=\"builder-audio-duration-audio-preview\"]')?.textContent?.trim() ?? ''"))->toBe('02:00');
});

test('builder preserves content text edits when selecting another block before blur', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Content Text Selection',
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
                        'id' => 'block-content-switch',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<h2>Texto inicial</h2><p>Descricao inicial</p>',
                        'required' => false,
                    ],
                    [
                        'id' => 'block-text-switch',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-content-switch"]')
        ->assertVisible('[data-testid="content-text-editor"]');

    $page->script(<<<'JS'
        () => {
            const editor = document.querySelector('[data-testid="content-text-editor"]');

            if (! editor) {
                throw new Error('Editor nao encontrado');
            }

            editor.innerHTML = '<h2>Texto sem blur</h2><p>Persistido ao trocar bloco</p>';
            editor.dispatchEvent(new Event('input', { bubbles: true }));
        }
    JS);

    $page->click('[data-testid="preview-block-block-text-switch"]')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $savedMarkup = (string) data_get($stage->fresh()->meta, 'builder.blocks.0.placeholder', '');

    expect($savedMarkup)->toContain('Texto sem blur');
    expect($savedMarkup)->toContain('Persistido ao trocar bloco');
});

test('builder options editor updates preview and persists the edited option item', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Options',
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
                        'id' => 'block-options',
                        'type' => 'single_choice',
                        'label' => null,
                        'required' => false,
                        'options_intro_type' => 'text',
                        'options_intro_title' => 'Titulo antigo',
                        'options_intro_description' => 'Descricao antiga',
                        'option_items' => [
                            [
                                'id' => 'option-1',
                                'label' => 'Opcao antiga',
                                'points' => 0,
                                'value' => 'A',
                                'destination' => 'next_stage',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-options"]')
        ->fill('[data-testid="options-intro-title"]', 'Pergunta nova')
        ->click('[data-testid="option-item-settings-option-1"]')
        ->fill('[data-testid="option-item-label-option-1"]', 'Opcao editada')
        ->assertSee('Pergunta nova')
        ->assertSee('Opcao editada')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $savedStage = $stage->fresh();

    expect(data_get($savedStage->meta, 'builder.blocks.0.options_intro_title'))->toBe('Pergunta nova');
    expect(data_get($savedStage->meta, 'builder.blocks.0.option_items.0.label'))->toBe('Opcao editada');
});

test('builder creates edits previews and persists arguments and before after blocks', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Argumentos Comparativo',
    ]);

    $stage = $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => createEmptyStagePayload(),
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="palette-block-arguments"]')
        ->assertVisible('[data-testid="arguments-option-0"]')
        ->fill('[data-testid="arguments-option-0"]', 'Atendimento consultivo')
        ->click('[data-testid="add-arguments-option"]')
        ->fill('[data-testid="arguments-option-1"]', 'Implantacao acompanhada')
        ->assertSee('Atendimento consultivo')
        ->assertSee('Implantacao acompanhada')
        ->click('[data-testid="palette-block-before_after"]')
        ->assertVisible('[data-testid="before_after-option-0"]')
        ->fill('[data-testid="before_after-option-0"]', 'Processo manual')
        ->fill('[data-testid="before_after-option-1"]', 'Fluxo automatizado')
        ->assertSee('Processo manual')
        ->assertSee('Fluxo automatizado')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $savedBlocks = collect(data_get($stage->fresh()->meta, 'builder.blocks', []))->keyBy('type');

    expect($savedBlocks['arguments']['options'])->toBe([
        'Atendimento consultivo',
        'Implantacao acompanhada',
    ]);
    expect($savedBlocks['before_after']['options'])->toBe([
        'Processo manual',
        'Fluxo automatizado',
    ]);

    $page->click('[data-testid="preview-block-'.$savedBlocks['arguments']['id'].'"]')
        ->fill('[data-testid="arguments-option-0"]', 'Diagnostico personalizado')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertSee('Diagnostico personalizado')
        ->assertNoJavaScriptErrors();

    $editedArguments = collect(data_get($stage->fresh()->meta, 'builder.blocks', []))
        ->firstWhere('type', 'arguments');

    expect($editedArguments['options'][0])->toBe('Diagnostico personalizado');
});

test('builder normalizes yes no option labels in preview', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Yes No Labels',
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
                        'id' => 'block-yes-no',
                        'type' => 'yes_no',
                        'label' => '',
                        'required' => false,
                        'options' => ['sim', 'nao'],
                        'option_items' => [
                            ['id' => 'yes', 'label' => 'sim', 'points' => 1, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'no', 'label' => 'nao', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('✅ Sim')
        ->assertSee('🚫 Nao')
        ->assertNoJavaScriptErrors();
});

test('builder preserves option image layout, detail position, and style in preview', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Option Images',
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
                    'id' => 'block-options-image',
                    'type' => 'single_choice',
                    'label' => '',
                    'required' => false,
                    'options_style' => 'highlight',
                    'options_layout' => 'grid_1',
                    'options_disposition' => 'text_image',
                    'options_detail' => 'value',
                    'options_detail_position' => 'end',
                    'option_items' => [[
                        'id' => 'option-image-1',
                        'label' => 'Opcao com imagem',
                        'points' => 1,
                        'value' => 'A',
                        'destination' => 'next_stage',
                        'image_url' => '/storage/funnels/opcao.png',
                    ]],
                ]],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertNoJavaScriptErrors();

    $page->click('[data-testid="preview-block-block-options-image"]')
        ->click('[data-testid="option-item-settings-option-image-1"]');

    expect($page->script("() => document.querySelector('[data-testid=\"option-item-image-url-option-image-1\"]')?.value ?? ''"))->toBe('/storage/funnels/opcao.png');
    expect($page->script("() => document.querySelector('[data-testid=\"option-detail-block-options-image-0\"]')?.className.includes('order-3') ?? false"))->toBeTrue();
    expect($page->script("() => document.querySelector('[data-testid=\"preview-block-block-options-image\"] button.w-full.border.text-sm.text-white')?.className.includes('bg-[#102f61]') ?? false"))->toBeTrue();
    expect($page->script("() => document.querySelector('[data-testid=\"preview-block-block-options-image\"] button.w-full.border.text-sm.text-white span.flex-1')?.className.includes('order-1') ?? false"))->toBeTrue();
});

test('builder sanitizes legacy image filenames to the public media route in previews', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Image Sanitization',
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
                        'id' => 'block-image-legacy',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => 'xgihEuimnBLZmh7u6GaBy9FtzITBRE7fTUMnJFDy.png',
                        'required' => false,
                    ],
                    [
                        'id' => 'block-carousel-legacy',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'option_items' => [
                            [
                                'id' => 'carousel-item-legacy',
                                'label' => 'Slide',
                                'value' => 'xgihEuimnBLZmh7u6GaBy9FtzITBRE7fTUMnJFDy.png',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-image-legacy"]');

    expect($page->script("() => document.querySelector('img[alt=\"Preview da imagem\"]')?.getAttribute('src') ?? ''"))->toBe('/media/xgihEuimnBLZmh7u6GaBy9FtzITBRE7fTUMnJFDy.png');

    $page->click('[data-testid="preview-block-block-carousel-legacy"]');

    expect($page->script("() => document.querySelector('img[alt=\"Imagem do item\"]')?.getAttribute('src') ?? ''"))->toBe('/media/xgihEuimnBLZmh7u6GaBy9FtzITBRE7fTUMnJFDy.png');
});

test('builder previews edits and persists video configuration', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Video',
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
                    'id' => 'video-block',
                    'type' => 'video',
                    'label' => '',
                    'placeholder' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'required' => false,
                    'video_ratio' => '16:9',
                ]],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertVisible('[data-testid="builder-video-preview-video-block"]');

    $page->script(<<<'JS'
        () => document.querySelector('[data-testid="preview-block-video-block"]')
            ?.dispatchEvent(new MouseEvent('click', { bubbles: true }))
    JS);

    $page
        ->fill('input[placeholder="https://www.youtube.com/watch?v=..."]', 'https://www.youtube.com/shorts/kQm_g3DcocA?feature=share')
        ->click('[data-testid="builder-component-tab-appearance"]')
        ->assertNoJavaScriptErrors();

    $page->script(<<<'JS'
        () => {
            const ratioSelect = Array.from(document.querySelectorAll('select'))
                .find((select) => Array.from(select.options).some((option) => option.value === '16:9'));

            if (! ratioSelect) {
                throw new Error('Seletor de proporcao do video nao encontrado');
            }

            ratioSelect.value = '1:1';
            ratioSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    JS);

    expect($page->script("() => document.querySelector('[data-testid=\"builder-video-preview-video-block\"]')?.getAttribute('src') ?? ''"))
        ->toBe('https://www.youtube.com/embed/kQm_g3DcocA');
    expect($page->script("() => document.querySelector('[data-testid=\"builder-video-preview-video-block\"]')?.parentElement?.className.includes('aspect-square') ?? false"))
        ->toBeTrue();

    $page->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertVisible('[data-testid="builder-video-preview-video-block"]')
        ->assertNoJavaScriptErrors();

    $savedVideo = collect(data_get($stage->fresh()->meta, 'builder.blocks', []))->firstWhere('type', 'video');

    expect($savedVideo['placeholder'])->toBe('https://www.youtube.com/shorts/kQm_g3DcocA?feature=share');
    expect($savedVideo['video_ratio'])->toBe('1:1');
});

test('builder retries stale csrf uploads and stores the returned audio url', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Audio Upload Retry',
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
                        'id' => 'block-audio-upload',
                        'type' => 'audio',
                        'label' => 'Audio',
                        'required' => false,
                        'audio_src' => '',
                        'audio_sender' => 'Equipe',
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-audio-upload"]');

    $page->script(<<<'JS'
        () => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            const originalFetch = window.fetch.bind(window);

            if (!(meta instanceof HTMLMetaElement)) {
                throw new Error('Meta CSRF nao encontrada');
            }

            meta.setAttribute('content', 'csrf-stale-token');
            document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';

            let uploadCalls = 0;

            window.fetch = async (input, init = {}) => {
                const url = typeof input === 'string' ? input : input instanceof Request ? input.url : String(input);

                if (url === window.location.href) {
                    return new Response(
                        '<!DOCTYPE html><html><head><meta name="csrf-token" content="fresh-csrf-token"></head><body></body></html>',
                        {
                            status: 200,
                            headers: { 'Content-Type': 'text/html' },
                        },
                    );
                }

                if (url.endsWith('/media')) {
                    uploadCalls += 1;

                    if (!(init.body instanceof FormData)) {
                        throw new Error('O upload nao enviou FormData');
                    }

                    const kind = init.body.get('kind');
                    const file = init.body.get('file');

                    if (kind !== 'audio' || !(file instanceof File)) {
                        throw new Error('O upload nao enviou os campos esperados');
                    }

                    if (uploadCalls === 1) {
                        return new Response('', { status: 419 });
                    }

                    return new Response(
                        JSON.stringify({
                            url: 'https://cdn.example.com/funnels/retry-audio.mp3',
                        }),
                        {
                            status: 200,
                            headers: { 'Content-Type': 'application/json' },
                        },
                    );
                }

                return originalFetch(input, init);
            };

            window.__builderUploadCalls = () => uploadCalls;
        }
    JS);

    $uploadedUrl = $page->script(<<<'JS'
        async () => {
            const fileInput = document.querySelector('[data-testid="builder-audio-file-input"]');
            const audioSourceInput = document.querySelector('[data-testid="builder-audio-src-input"]');

            if (!(fileInput instanceof HTMLInputElement)) {
                throw new Error('Input de upload de audio nao encontrado');
            }

            if (!(audioSourceInput instanceof HTMLInputElement)) {
                throw new Error('Campo de URL do audio nao encontrado');
            }

            const file = new File([new Uint8Array([73, 68, 51, 4, 0, 0, 0, 0, 0, 0])], 'retry-audio.mp3', {
                type: 'audio/mpeg',
            });
            const transfer = new DataTransfer();

            transfer.items.add(file);

            Object.defineProperty(fileInput, 'files', {
                value: transfer.files,
                configurable: true,
            });

            fileInput.dispatchEvent(new Event('change', { bubbles: true }));

            for (let attempt = 0; attempt < 40; attempt += 1) {
                await new Promise((resolve) => window.setTimeout(resolve, 100));

                if (audioSourceInput.value === 'https://cdn.example.com/funnels/retry-audio.mp3') {
                    return audioSourceInput.value;
                }
            }

            throw new Error('Upload de audio nao refletiu no campo apos o retry');
        }
    JS);

    expect($uploadedUrl)->toBe('https://cdn.example.com/funnels/retry-audio.mp3');
    expect($page->script('() => window.__builderUploadCalls?.() ?? 0'))->toBe(2);

    $page->assertNoJavaScriptErrors();
});

test('builder retries stale csrf uploads and stores the returned image url', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Image Upload Retry',
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
                    'id' => 'block-image-upload',
                    'type' => 'image',
                    'label' => 'Imagem',
                    'required' => false,
                    'placeholder' => '',
                ]],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-image-upload"]');

    $page->script(<<<'JS'
        () => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            const originalFetch = window.fetch.bind(window);

            if (!(meta instanceof HTMLMetaElement)) {
                throw new Error('Meta CSRF nao encontrada');
            }

            meta.setAttribute('content', 'csrf-stale-token');
            document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';

            let uploadCalls = 0;

            window.fetch = async (input, init = {}) => {
                const url = typeof input === 'string' ? input : input instanceof Request ? input.url : String(input);

                if (url === window.location.href) {
                    return new Response(
                        '<!DOCTYPE html><html><head><meta name="csrf-token" content="fresh-csrf-token"></head><body></body></html>',
                        {
                            status: 200,
                            headers: { 'Content-Type': 'text/html' },
                        },
                    );
                }

                if (url.endsWith('/media')) {
                    uploadCalls += 1;

                    if (!(init.body instanceof FormData)) {
                        throw new Error('O upload nao enviou FormData');
                    }

                    const kind = init.body.get('kind');
                    const file = init.body.get('file');

                    if (kind !== 'image' || !(file instanceof File)) {
                        throw new Error('O upload nao enviou os campos esperados');
                    }

                    if (uploadCalls === 1) {
                        return new Response('', { status: 419 });
                    }

                    await new Promise((resolve) => window.setTimeout(resolve, 400));

                    return new Response(
                        JSON.stringify({
                            url: 'https://cdn.example.com/funnels/retry-image.png',
                        }),
                        {
                            status: 200,
                            headers: { 'Content-Type': 'application/json' },
                        },
                    );
                }

                return originalFetch(input, init);
            };

            window.__builderImageUploadCalls = () => uploadCalls;
        }
    JS);

    $uploadResult = $page->script(<<<'JS'
        async () => {
            const fileInput = document.querySelector('[data-testid="builder-image-file-input"]');

            if (!(fileInput instanceof HTMLInputElement)) {
                throw new Error('Input de upload de imagem nao encontrado');
            }

            const file = new File([new Uint8Array([137, 80, 78, 71, 13, 10, 26, 10])], 'retry-image.png', {
                type: 'image/png',
            });
            const transfer = new DataTransfer();

            transfer.items.add(file);

            Object.defineProperty(fileInput, 'files', {
                value: transfer.files,
                configurable: true,
            });

            fileInput.dispatchEvent(new Event('change', { bubbles: true }));

            let loadingStatusWasVisible = false;
            let emptyMessageWasVisibleWhileUploading = false;

            for (let attempt = 0; attempt < 40; attempt += 1) {
                await new Promise((resolve) => window.setTimeout(resolve, 100));

                const preview = document.querySelector('[data-testid="preview-block-block-image-upload"]');
                const src = document.querySelector('img[alt="Preview da imagem"]')?.getAttribute('src') ?? '';

                if (preview?.textContent?.includes('Carregando imagem')) {
                    loadingStatusWasVisible = true;
                    emptyMessageWasVisibleWhileUploading ||= preview?.textContent?.includes('Imagem nao configurada') ?? false;
                }

                if (src === 'https://cdn.example.com/funnels/retry-image.png') {
                    return {
                        src,
                        loadingStatusWasVisible,
                        emptyMessageWasVisibleWhileUploading,
                    };
                }
            }

            throw new Error('Upload de imagem nao refletiu no preview apos o retry');
        }
    JS);

    expect($uploadResult)->toBe([
        'src' => 'https://cdn.example.com/funnels/retry-image.png',
        'loadingStatusWasVisible' => true,
        'emptyMessageWasVisibleWhileUploading' => false,
    ]);
    expect($page->script('() => window.__builderImageUploadCalls?.() ?? 0'))->toBe(2);

    $page->assertNoJavaScriptErrors();
});

test('builder retries stale csrf uploads and stores the returned carousel image url', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Carousel Upload Retry',
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
                    'id' => 'block-carousel-upload',
                    'type' => 'carousel',
                    'label' => '',
                    'required' => false,
                    'carousel_layout' => 'image_text',
                    'option_items' => [[
                        'id' => 'carousel-upload-item',
                        'label' => '',
                        'value' => '',
                        'description' => 'Slide inicial',
                    ]],
                ]],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-carousel-upload"]');

    $page->script(<<<'JS'
        () => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            const originalFetch = window.fetch.bind(window);

            if (!(meta instanceof HTMLMetaElement)) {
                throw new Error('Meta CSRF nao encontrada');
            }

            meta.setAttribute('content', 'csrf-stale-token');
            document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';

            let uploadCalls = 0;

            window.fetch = async (input, init = {}) => {
                const url = typeof input === 'string' ? input : input instanceof Request ? input.url : String(input);

                if (url === window.location.href) {
                    return new Response(
                        '<!DOCTYPE html><html><head><meta name="csrf-token" content="fresh-csrf-token"></head><body></body></html>',
                        {
                            status: 200,
                            headers: { 'Content-Type': 'text/html' },
                        },
                    );
                }

                if (url.endsWith('/media')) {
                    uploadCalls += 1;

                    if (!(init.body instanceof FormData)) {
                        throw new Error('O upload nao enviou FormData');
                    }

                    const kind = init.body.get('kind');
                    const file = init.body.get('file');

                    if (kind !== 'image' || !(file instanceof File)) {
                        throw new Error('O upload nao enviou os campos esperados');
                    }

                    if (uploadCalls === 1) {
                        return new Response('', { status: 419 });
                    }

                    return new Response(
                        JSON.stringify({
                            url: 'https://cdn.example.com/funnels/retry-carousel.png',
                        }),
                        {
                            status: 200,
                            headers: { 'Content-Type': 'application/json' },
                        },
                    );
                }

                return originalFetch(input, init);
            };

            window.__builderCarouselUploadCalls = () => uploadCalls;
        }
    JS);

    $uploadedUrl = $page->script(<<<'JS'
        async () => {
            const pickerButton = document.querySelector('[data-testid="builder-carousel-select-image-carousel-upload-item"]');
            const fileInput = document.querySelector('[data-testid="builder-carousel-file-input"]');
            const urlInput = document.querySelector('[data-testid="builder-carousel-image-url-carousel-upload-item"]');

            if (!(pickerButton instanceof HTMLButtonElement)) {
                throw new Error('Botao de upload do carousel nao encontrado');
            }

            if (!(fileInput instanceof HTMLInputElement)) {
                throw new Error('Input de upload do carousel nao encontrado');
            }

            if (!(urlInput instanceof HTMLInputElement)) {
                throw new Error('Campo de URL do carousel nao encontrado');
            }

            pickerButton.click();

            const file = new File([new Uint8Array([137, 80, 78, 71, 13, 10, 26, 10])], 'retry-carousel.png', {
                type: 'image/png',
            });
            const transfer = new DataTransfer();

            transfer.items.add(file);

            Object.defineProperty(fileInput, 'files', {
                value: transfer.files,
                configurable: true,
            });

            fileInput.dispatchEvent(new Event('change', { bubbles: true }));

            for (let attempt = 0; attempt < 40; attempt += 1) {
                await new Promise((resolve) => window.setTimeout(resolve, 100));

                if (urlInput.value === 'https://cdn.example.com/funnels/retry-carousel.png') {
                    return urlInput.value;
                }
            }

            throw new Error('Upload de imagem do carousel nao refletiu no campo apos o retry');
        }
    JS);

    expect($uploadedUrl)->toBe('https://cdn.example.com/funnels/retry-carousel.png');
    expect($page->script('() => window.__builderCarouselUploadCalls?.() ?? 0'))->toBe(2);

    $page->assertNoJavaScriptErrors();
});

test('builder can reorder preview blocks and persist the new order', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Block Reorder',
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
                        'id' => 'block-first',
                        'type' => 'text',
                        'label' => 'Primeiro',
                        'placeholder' => '',
                        'required' => false,
                    ],
                    [
                        'id' => 'block-second',
                        'type' => 'text',
                        'label' => 'Segundo',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertVisible('[data-testid="preview-block-block-first"]')
        ->assertVisible('[data-testid="preview-block-block-second"]');

    expect($page->script(<<<'JS'
        () => Array.from(document.querySelectorAll('[data-testid^="preview-block-"]'))
            .map((element) => element.getAttribute('data-testid'))
    JS))->toBe([
        'preview-block-block-first',
        'preview-block-block-second',
    ]);

    $page->drag('[data-testid="preview-block-block-second"]', '[data-testid="preview-block-block-first"]')
        ->wait(0.5)
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $savedBlocks = data_get($stage->fresh()->meta, 'builder.blocks', []);

    expect(array_column($savedBlocks, 'id'))->toBe(['block-second', 'block-first']);
});

test('builder can reorder option items and persist the new order', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Option Reorder',
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
                        'id' => 'block-options-reorder',
                        'type' => 'single_choice',
                        'label' => null,
                        'required' => false,
                        'options_intro_type' => 'text',
                        'options_intro_title' => '',
                        'options_intro_description' => '',
                        'option_items' => [
                            ['id' => 'option-1', 'label' => 'Opcao 1', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'option-2', 'label' => 'Opcao 2', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-options-reorder"]');

    expect($page->script(<<<'JS'
        () => Array.from(document.querySelectorAll('[data-testid^="option-item-option-"]'))
            .map((element) => element.getAttribute('data-testid'))
    JS))->toBe([
        'option-item-option-1',
        'option-item-option-2',
    ]);

    $page->drag('[data-testid="option-item-option-2"]', '[data-testid="option-item-option-1"]')
        ->wait(0.5)
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    $savedItems = data_get($stage->fresh()->meta, 'builder.blocks.0.option_items', []);

    expect(array_column($savedItems, 'id'))->toBe(['option-2', 'option-1']);
});

test('builder autosave persists changes and shows feedback', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Autosave',
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
                        'id' => 'block-autosave',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-autosave"]')
        ->fill('[data-testid="block-label-input"]', 'Nome atualizado')
        ->assertVisible('[data-testid="builder-unsaved-status"]')
        ->wait(3)
        ->assertVisible('[data-testid="builder-autosave-status"]')
        ->assertSee('Salvo')
        ->assertNoJavaScriptErrors();

    expect(data_get($stage->fresh()->meta, 'builder.blocks.0.label'))->toBe('Nome atualizado');
});

test('builder persists the latest change made while a save is already in progress', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Pending Save',
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
                        'id' => 'block-pending-save',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-pending-save"]')
        ->fill('[data-testid="block-label-input"]', 'Primeiro valor')
        ->click('[data-testid="builder-save-button"]')
        ->fill('[data-testid="block-label-input"]', 'Valor final')
        ->assertVisible('[data-testid="builder-unsaved-status"]')
        ->wait(4)
        ->assertSee('Valor final')
        ->assertNoJavaScriptErrors();

    expect(data_get($stage->fresh()->meta, 'builder.blocks.0.label'))->toBe('Valor final');
});

test('builder manual save clears pending state and keeps persisted changes', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Manual Save',
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
                        'id' => 'block-manual-save',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-manual-save"]')
        ->fill('[data-testid="block-label-input"]', 'Nome salvo manualmente')
        ->assertVisible('[data-testid="builder-unsaved-status"]')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNotPresent('[data-testid="builder-unsaved-status"]')
        ->assertVisible('[data-testid="builder-autosave-status"]')
        ->assertSee('Salvo')
        ->assertNoJavaScriptErrors();

    expect(data_get($stage->fresh()->meta, 'builder.blocks.0.label'))->toBe('Nome salvo manualmente');
});

test('builder stores a stable selected stage reference after save', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Reload Stage Restore',
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
                        'id' => 'block-stage-one-reload',
                        'type' => 'text',
                        'label' => 'Campo etapa um',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $stageTwo = $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'block-stage-two-reload',
                        'type' => 'text',
                        'label' => 'Campo etapa dois',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="builder-stage-item-2"]')
        ->assertSee('Campo etapa dois')
        ->assertDontSee('Campo etapa um')
        ->click('[data-testid="preview-block-block-stage-two-reload"]')
        ->fill('[data-testid="block-label-input"]', 'Campo etapa dois persistido')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertSee('Campo etapa dois persistido')
        ->assertDontSee('Campo etapa um')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => window.sessionStorage.getItem('builder:selected-stage:{$funnel->id}')"))->toBe("stage:{$stageTwo->id}");

    expect(data_get($stageTwo->fresh()->meta, 'builder.blocks.0.label'))->toBe('Campo etapa dois persistido');
});

test('builder manual save persists content text even while the rich text editor is focused', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Manual Save Content Text',
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
                        'id' => 'block-manual-save-content',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<h2>Texto antigo</h2><p>Descricao antiga</p>',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-block-manual-save-content"]')
        ->assertVisible('[data-testid="content-text-editor"]');

    $page->script(<<<'JS'
        () => {
            const editor = document.querySelector('[data-testid="content-text-editor"]');

            if (! editor) {
                throw new Error('Editor nao encontrado');
            }

            editor.focus();
            editor.innerHTML = '<h2>Texto salvo manualmente</h2><p>Persistido com save manual</p>';
            editor.dispatchEvent(new Event('input', { bubbles: true }));
        }
    JS);

    $page
        ->assertVisible('[data-testid="builder-unsaved-status"]')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNotPresent('[data-testid="builder-unsaved-status"]')
        ->assertVisible('[data-testid="builder-autosave-status"]')
        ->assertSee('Salvo')
        ->assertNoJavaScriptErrors();

    $savedMarkup = (string) data_get($stage->fresh()->meta, 'builder.blocks.0.placeholder', '');

    expect($savedMarkup)->toContain('Texto salvo manualmente');
    expect($savedMarkup)->toContain('Persistido com save manual');
});

test('builder manual save keeps the current stage selected', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Current Stage Save',
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
                        'id' => 'block-stage-one',
                        'type' => 'text',
                        'label' => 'Campo etapa um',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => [
            'builder' => [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'block-stage-two',
                        'type' => 'text',
                        'label' => 'Campo etapa dois',
                        'placeholder' => '',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="builder-stage-item-2"]')
        ->assertSee('Campo etapa dois')
        ->assertDontSee('Campo etapa um')
        ->click('[data-testid="preview-block-block-stage-two"]')
        ->fill('[data-testid="block-label-input"]', 'Campo etapa dois atualizado')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertSee('Campo etapa dois atualizado')
        ->assertDontSee('Campo etapa um')
        ->assertNoJavaScriptErrors();
});

test('builder stage menu can duplicate and delete stages', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Stage Menu',
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => createEmptyStagePayload(),
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => createEmptyStagePayload(),
        ],
        [
            'name' => 'Etapa 3',
            'stage_order' => 3,
            'meta' => createEmptyStagePayload(),
        ],
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertSee('Etapa 1')
        ->assertSee('Etapa 2')
        ->assertSee('Etapa 3');

    $page->script("() => document.querySelector('[data-testid^=\"stage-menu-trigger-\"]')?.click()");

    $page->click('[data-testid^="stage-menu-duplicate-"]')
        ->wait(0.5)
        ->assertSee('Etapa 1 copia');

    $page->script("() => document.querySelector('[data-testid^=\"stage-menu-trigger-\"]')?.click()");

    $page->click('[data-testid^="stage-menu-delete-"]')
        ->wait(0.5)
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.body.textContent.includes('Etapa 1 copia')"))->toBeTrue();
});

test('builder saves structured conditional display rules without free text inputs', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Builder Display Rules',
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
                        'id' => 'source-block',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => '',
                        'required' => false,
                    ],
                    [
                        'id' => 'target-block',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<p>Condicional</p>',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->click('[data-testid="preview-block-target-block"]')
        ->click('[data-testid="builder-component-tab-display"]')
        ->click('[data-testid="display-rule-add-button"]')
        ->select('[data-testid="display-rule-source-0-0"]', 'source-block')
        ->select('[data-testid="display-rule-operator-0-0"]', 'filled')
        ->click('[data-testid="builder-save-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect(data_get($stage->fresh()->meta, 'builder.blocks.1.display_rule_groups.0.rules.0.source_block_id'))->toBe('source-block');
    expect(data_get($stage->fresh()->meta, 'builder.blocks.1.display_rule_groups.0.rules.0.operator'))->toBe('filled');
});

test('funnel toolbar opens the public result and the new funnel settings page', function () {
    config()->set('inovaform.publication.custom_domain_diagnostics_enabled', false);

    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Configurações Browser',
        'slug' => 'configuracoes-browser',
    ]);
    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => createEmptyStagePayload(),
    ]);

    $page = loginThroughBrowser($user)
        ->click('Abrir Builder')
        ->assertVisible('[data-testid="funnel-settings-button"]')
        ->assertVisible('[data-testid="funnel-preview-button"]');

    expect($page->script("() => document.querySelector('[data-testid=\"funnel-preview-button\"]')?.getAttribute('href')"))->toBe('/f/configuracoes-browser')
        ->and($page->script("() => document.querySelector('[data-testid=\"funnel-preview-button\"]')?.getAttribute('target')"))->toBe('_blank');

    $page->click('[data-testid="funnel-settings-button"]')
        ->wait(0.5)
        ->assertSee('Configurações do funil')
        ->click('[data-testid="settings-tab-seo"]')
        ->assertVisible('[data-testid="settings-seo-panel"]')
        ->click('[data-testid="settings-tab-domain"]')
        ->assertVisible('[data-testid="settings-domain-panel"]')
        ->click('[data-testid="settings-tab-connections"]')
        ->assertVisible('[data-testid="settings-connections-panel"]')
        ->click('Design')
        ->assertDontSee('PUBLICACAO')
        ->assertNoJavaScriptErrors();

    expect($page->script('() => window.location.pathname'))->toBe(route('funnels.design', $funnel, absolute: false));
});
