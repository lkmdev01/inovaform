<?php

use App\Models\Funnel;
use App\Models\FunnelSubmission;
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

function loginForPublicBrowser(User $user): mixed
{
    return visit('/login')
        ->assertSee('Acessar conta')
        ->type('#email', $user->email)
        ->type('#password', 'password')
        ->press('Entrar no painel')
        ->wait(1);
}

test('public funnel reveals delayed block after configured seconds', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil com atraso',
        'slug' => 'funil-com-atraso',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Etapa 1',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'intro',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<p>Conteudo inicial</p>',
                        'required' => false,
                    ],
                    [
                        'id' => 'delayed',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<p>Aparece com atraso</p>',
                        'required' => false,
                        'show_after_seconds' => 2,
                    ],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Conteudo inicial');

    expect($page->script("() => document.body.textContent.includes('Aparece com atraso')"))->toBeFalse();

    $page->wait(3)
        ->assertSee('Aparece com atraso')
        ->assertNoJavaScriptErrors();
});

test('public funnel ignores blank open link buttons instead of opening whitespace urls', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Botao Link Em Branco',
        'slug' => 'funil-botao-link-em-branco',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'CTA',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'blank-open-link-button',
                        'type' => 'button',
                        'label' => 'Abrir link',
                        'required' => false,
                        'button_action' => 'open_link',
                        'button_link' => '   ',
                        'button_open_new_tab' => true,
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}");

    $page->script(<<<'JS'
        () => {
            window.__openedUrl = null;
            window.__openedTarget = null;
            window.open = (url, target) => {
                window.__openedUrl = url;
                window.__openedTarget = target;

                return null;
            };
        }
    JS);

    $page->click('Abrir link')
        ->wait(0.3)
        ->assertNoJavaScriptErrors();

    expect($page->script('() => window.__openedUrl'))->toBeNull();
    expect($page->script('() => window.__openedTarget'))->toBeNull();
});

test('public funnel loading starts navigation only after the block becomes visible', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Loading Visivel',
        'slug' => 'funil-loading-visivel',
        'is_active' => true,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => 'Primeira etapa',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        [
                            'id' => 'loading-delayed',
                            'type' => 'loading',
                            'label' => 'Processando',
                            'placeholder' => 'Aguarde',
                            'required' => false,
                            'show_after_seconds' => 2,
                            'loading_start_seconds' => 5,
                            'loading_duration_seconds' => 1,
                            'loading_navigation_action' => 'next_stage',
                            'loading_target_stage_order' => 'next',
                        ],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => 'Etapa 2',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        [
                            'id' => 'final-copy',
                            'type' => 'content_text',
                            'label' => null,
                            'placeholder' => '<p>Etapa seguinte carregada</p>',
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Primeira etapa');

    $page->wait(1);
    expect($page->script("() => document.body.textContent.includes('Etapa seguinte carregada')"))->toBeFalse();

    $page->wait(3)
        ->assertSee('Etapa seguinte carregada')
        ->assertNoJavaScriptErrors();
});

test('public funnel loading open link fires only once after the countdown finishes', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Loading Link',
        'slug' => 'funil-loading-link',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Aguarde',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'loading-open-link',
                        'type' => 'loading',
                        'label' => 'Preparando redirecionamento',
                        'placeholder' => 'Abrindo oferta',
                        'required' => false,
                        'loading_start_seconds' => 0,
                        'loading_duration_seconds' => 1,
                        'loading_navigation_action' => 'open_link',
                        'loading_link' => 'https://example.com/loading-offer',
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}");

    $page->script(<<<'JS'
        () => {
            window.__openedUrls = [];
            window.open = (url, target) => {
                window.__openedUrls.push({ url, target });

                return null;
            };
        }
    JS);

    $page->wait(3)
        ->assertSee('Abrindo oferta')
        ->assertNoJavaScriptErrors();

    expect($page->script('() => window.__openedUrls.length'))->toBe(1);
    expect($page->script('() => window.__openedUrls[0]?.url ?? null'))->toBe('https://example.com/loading-offer');
    expect($page->script('() => window.__openedUrls[0]?.target ?? null'))->toBe('_blank');
});

test('public funnel reveals conditional block after matching option answer', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil condicional',
        'slug' => 'funil-condicional',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Etapa 1',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'challenge_block',
                        'type' => 'single_choice',
                        'label' => '',
                        'required' => false,
                        'options_disable_auto_follow' => true,
                        'option_items' => [
                            ['id' => 'opt-1', 'label' => 'Leads', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'opt-2', 'label' => 'Vendas', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                        ],
                    ],
                    [
                        'id' => 'conditional-copy',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<p>Bloco condicional exibido</p>',
                        'required' => false,
                        'display_rules' => ['challenge_block=Leads'],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}");

    expect($page->script("() => document.body.textContent.includes('Bloco condicional exibido')"))->toBeFalse();

    $page->click('Leads')
        ->wait(1)
        ->assertSee('Bloco condicional exibido')
        ->assertNoJavaScriptErrors();
});

test('public funnel auto advances when hidden required blocks stay hidden', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil auto avancar condicional',
        'slug' => 'funil-auto-avancar-condicional',
        'is_active' => true,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => 'Escolha um caminho',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        [
                            'id' => 'challenge_block',
                            'type' => 'single_choice',
                            'label' => '',
                            'required' => true,
                            'option_items' => [
                                ['id' => 'opt-1', 'label' => 'Leads', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                                ['id' => 'opt-2', 'label' => 'Vendas', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                            ],
                        ],
                        [
                            'id' => 'conditional_required_text',
                            'type' => 'text',
                            'label' => 'Detalhe extra',
                            'placeholder' => 'Explique melhor',
                            'required' => true,
                            'display_rules' => ['challenge_block=Vendas'],
                        ],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => 'Etapa 2',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        [
                            'id' => 'final-copy',
                            'type' => 'content_text',
                            'label' => null,
                            'placeholder' => '<p>Etapa 2 carregada</p>',
                            'required' => false,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Escolha um caminho');

    expect($page->script("() => document.querySelector('[data-testid=\"public-options-intro-challenge_block\"]')"))->toBeNull();

    $page->script(<<<'JS'
        () => {
            const targetButton = Array.from(document.querySelectorAll('button'))
                .find((button) => button.textContent?.includes('Leads'));

            if (! targetButton) {
                throw new Error('Botao Leads nao encontrado');
            }

            targetButton.click();
        }
    JS);

    $page->wait(1)
        ->assertSee('Etapa 2 carregada')
        ->assertNoJavaScriptErrors();
});

test('public funnel yes no blocks auto advance to the next stage', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil sim nao',
        'slug' => 'funil-sim-nao',
        'is_active' => true,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => 'Pergunta',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [[
                        'id' => 'consent_block',
                        'type' => 'yes_no',
                        'label' => '',
                        'required' => true,
                        'option_items' => [
                            ['id' => 'yes', 'label' => 'Sim', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'no', 'label' => 'Nao', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                        ],
                    ]],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => 'Etapa 2',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [[
                        'id' => 'next-copy',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<p>Avancou pelo sim nao</p>',
                        'required' => false,
                    ]],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Pergunta')
        ->click('Sim')
        ->wait(1)
        ->assertSee('Avancou pelo sim nao')
        ->assertNoJavaScriptErrors();
});

test('public funnel multiple choice waits for manual continue before advancing', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil multipla escolha browser',
        'slug' => 'funil-multipla-escolha-browser',
        'is_active' => true,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => 'Escolha interesses',
                    'subtitle' => '',
                    'button_text' => 'Continuar',
                    'blocks' => [[
                        'id' => 'interest_block',
                        'type' => 'multiple_choice',
                        'label' => '',
                        'required' => true,
                        'options_required_selection' => true,
                        'options_allow_multiple' => true,
                        'option_items' => [
                            ['id' => 'opt-1', 'label' => 'Leads', 'points' => 0, 'value' => 'A', 'destination' => 'next_stage'],
                            ['id' => 'opt-2', 'label' => 'Vendas', 'points' => 0, 'value' => 'B', 'destination' => 'next_stage'],
                        ],
                    ]],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => 'Etapa 2',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [[
                        'id' => 'next-copy',
                        'type' => 'content_text',
                        'label' => null,
                        'placeholder' => '<p>Avancou pela multipla escolha</p>',
                        'required' => false,
                    ]],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Escolha interesses');

    $page->click('Leads')
        ->click('Vendas')
        ->wait(0.5)
        ->assertSee('Continuar')
        ->assertDontSee('Avancou pela multipla escolha')
        ->assertNoJavaScriptErrors();

    $page->click('Continuar')
        ->wait(1)
        ->assertSee('Avancou pela multipla escolha')
        ->assertNoJavaScriptErrors();
});

test('public funnel timer reaches zero instead of stopping at one second', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Timer Zero',
        'slug' => 'funil-timer-zero',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Timer',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'timer-zero',
                        'type' => 'timer',
                        'label' => '',
                        'required' => false,
                        'timer_seconds' => 1,
                        'timer_text' => 'Tempo restante [time]',
                    ],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Tempo restante')
        ->wait(2)
        ->assertSee('Tempo restante 00:00')
        ->assertNoJavaScriptErrors();
});

test('public funnel timer still renders countdown when no custom text is configured', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Timer Sem Texto',
        'slug' => 'funil-timer-sem-texto',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Timer',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'timer-without-copy',
                        'type' => 'timer',
                        'label' => '',
                        'required' => false,
                        'timer_seconds' => 2,
                        'timer_text' => '',
                    ],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('00:02')
        ->wait(3)
        ->assertSee('00:00')
        ->assertNoJavaScriptErrors();
});

test('public funnel notification rotates configured variations over time', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Notification Rotativa',
        'slug' => 'funil-notification-rotativa',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Notification',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'notification-rotating',
                        'type' => 'notification',
                        'label' => '',
                        'required' => false,
                        'notification_title' => '@1 chegou por @2',
                        'notification_description' => 'Canal mais recente: @2. Restam @3 vagas.',
                        'notification_avatar_url' => '@4',
                        'notification_interval_seconds' => 1,
                        'notification_duration_seconds' => 1,
                        'notification_variations' => [
                            ['id' => 'n1', 'value1' => 'Rafael', 'value2' => 'Instagram', 'value3' => '3', 'value4' => 'https://cdn.example.com/avatar-rafael.png'],
                            ['id' => 'n2', 'value1' => 'Beatriz', 'value2' => 'WhatsApp', 'value3' => '2', 'value4' => 'https://cdn.example.com/avatar-beatriz.png'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Rafael chegou por Instagram')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"public-notification-notification-rotating\"] img[alt=\"Avatar da notificacao\"]')?.getAttribute('src') ?? ''"))->toBe('https://cdn.example.com/avatar-rafael.png');

    $page->wait(2)
        ->assertSee('Beatriz chegou por WhatsApp')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"public-notification-notification-rotating\"] img[alt=\"Avatar da notificacao\"]')?.getAttribute('src') ?? ''"))->toBe('https://cdn.example.com/avatar-beatriz.png');
});

test('public funnel in-flow large notification does not clip its content', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Notification Grande',
        'slug' => 'funil-notification-grande',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Notification',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'notification-large-flow',
                        'type' => 'notification',
                        'label' => '',
                        'required' => false,
                        'notification_title' => '@1 acabou de entrar na campanha premium',
                        'notification_description' => 'Lead vindo de @2. Segmento: @3. Esta notificacao precisa de mais espaco para nao cortar o conteudo em tamanhos maiores.',
                        'notification_position' => 'default',
                        'notification_size' => 'large',
                        'notification_interval_seconds' => 4,
                        'notification_duration_seconds' => 4,
                        'notification_variations' => [
                            ['id' => 'n1', 'value1' => 'Marina', 'value2' => 'Meta Ads', 'value3' => 'Nutricao'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Marina acabou de entrar na campanha premium')
        ->assertNoJavaScriptErrors();

    $page->wait(1);

    expect($page->script("
        () => {
            const shell = document.querySelector('[data-testid=\"public-notification-notification-large-flow\"]');

            if (!shell) {
                return null;
            }

            return shell.scrollHeight > shell.clientHeight;
        }
    "))->toBeFalse();
});

test('public funnel audio supports rapid play pause and seek interactions', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Audio Publico',
        'slug' => 'funil-audio-publico',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Audio',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [[
                    'id' => 'audio-main',
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

    $page = visit("/f/{$funnel->slug}")
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
                window.__audioPlayCount = playCount;
                audio.dispatchEvent(new Event('play'));
            };

            audio.pause = () => {
                paused = true;
                pauseCount += 1;
                window.__audioPauseCount = pauseCount;
                audio.dispatchEvent(new Event('pause'));
            };

            audio.dispatchEvent(new Event('loadedmetadata'));
        }
    JS);

    $page->script(<<<'JS'
        () => {
            const toggle = document.querySelector('[data-testid="public-audio-toggle-audio-main"]');

            if (! toggle) {
                throw new Error('Toggle publico nao encontrado');
            }

            toggle.click();
            toggle.click();
        }
    JS);

    $page->script(<<<'JS'
        () => {
            const seek = document.querySelector('[data-testid="public-audio-seek-audio-main"]');

            if (! seek) {
                throw new Error('Seek publico nao encontrado');
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
            const toggle = document.querySelector('[data-testid="public-audio-toggle-audio-main"]');

            if (! toggle) {
                throw new Error('Toggle publico nao encontrado');
            }

            toggle.click();
        }
    JS);

    $page->wait(0.5)
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"public-audio-current-audio-main\"]')?.textContent?.trim() ?? ''"))->toBeIn(['00:59', '01:00']);
    expect($page->script("() => document.querySelector('[data-testid=\"public-audio-duration-audio-main\"]')?.textContent?.trim() ?? ''"))->toBe('02:00');
});

test('owner can publish from builder and submit the public funnel flow', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Browser Completo',
        'slug' => 'funil-browser-completo',
        'is_active' => false,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => 'Primeira etapa',
                    'subtitle' => 'Preencha seus dados',
                    'button_text' => 'Continuar',
                    'blocks' => [
                        ['id' => 'name_block', 'type' => 'text', 'label' => 'Nome', 'placeholder' => 'Digite seu nome', 'required' => true],
                        ['id' => 'email_block', 'type' => 'email', 'label' => 'Email', 'placeholder' => 'Digite seu email', 'required' => true],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => 'Final',
                    'subtitle' => 'Ultimo passo',
                    'button_text' => 'Enviar',
                    'blocks' => [
                        ['id' => 'phone_block', 'type' => 'phone', 'label' => 'Telefone', 'placeholder' => 'Digite seu telefone', 'required' => false],
                    ],
                ],
            ],
        ],
    ]);

    loginForPublicBrowser($owner);

    visit("/funnels/{$funnel->id}/builder")
        ->click('[data-testid="builder-publish-button"]')
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect($funnel->fresh()->is_active)->toBeTrue();

    visit("/f/{$funnel->slug}")
        ->fill('input[placeholder="Digite seu nome"]', 'Maria Browser')
        ->fill('input[placeholder="Digite seu email"]', 'maria@example.com')
        ->click('Continuar')
        ->wait(1)
        ->fill('input[placeholder="Digite seu telefone"]', '+5511999999999')
        ->click('Enviar')
        ->wait(1)
        ->assertVisible('[data-testid="public-funnel-completed"]')
        ->assertSee('Resposta enviada')
        ->assertNoJavaScriptErrors();

    $submission = FunnelSubmission::query()
        ->where('funnel_id', $funnel->id)
        ->latest('id')
        ->first();

    expect($submission)->not->toBeNull();
    expect($submission?->lead_name)->toBe('Maria Browser');
    expect($submission?->lead_email)->toBe('maria@example.com');
});

test('public block button prevents advancing with required fields empty or an invalid email', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil com validacao publica',
        'slug' => 'funil-com-validacao-publica',
        'is_active' => true,
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'title' => 'Seus dados',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [
                        ['id' => 'required-name', 'type' => 'text', 'label' => 'Nome', 'placeholder' => 'Nome obrigatorio', 'required' => true],
                        ['id' => 'required-email', 'type' => 'email', 'label' => 'Email', 'placeholder' => 'Email obrigatorio', 'required' => true],
                        ['id' => 'next-button', 'type' => 'button', 'label' => 'Proximo', 'required' => false, 'button_action' => 'next_stage', 'button_target_stage_order' => 'next'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'title' => 'Etapa confirmada',
                    'subtitle' => '',
                    'button_text' => '',
                    'blocks' => [],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->click('[data-testid="public-block-button-next-button"]')
        ->assertSee('Seus dados')
        ->assertSee('Campo obrigatório')
        ->assertDontSee('Etapa confirmada')
        ->fill('input[placeholder="Nome obrigatorio"]', 'Maria')
        ->fill('input[placeholder="Email obrigatorio"]', 'email-invalido')
        ->click('[data-testid="public-block-button-next-button"]')
        ->assertSee('E-mail inválido')
        ->assertDontSee('Etapa confirmada')
        ->fill('input[placeholder="Email obrigatorio"]', 'maria@example.com')
        ->click('[data-testid="public-block-button-next-button"]')
        ->assertSee('Etapa confirmada')
        ->assertNoJavaScriptErrors();
});

test('public funnel renders configured completion page after submit', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil com conclusao customizada',
        'slug' => 'funil-com-conclusao-customizada',
        'is_active' => true,
        'design_settings' => [
            'completion_page' => [
                'enabled' => true,
                'title' => 'Obrigado, {nome}',
                'description' => 'Seu cadastro foi recebido com sucesso.',
                'image_url' => 'https://example.com/success.png',
                'primary_button_text' => 'Ir para o site',
                'primary_button_url' => 'https://example.com',
                'primary_button_new_tab' => true,
                'secondary_button_text' => 'Fechar',
                'secondary_button_url' => '/',
                'secondary_button_new_tab' => false,
                'auto_redirect_url' => '',
                'auto_redirect_delay_seconds' => 0,
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Cadastro',
                'subtitle' => '',
                'button_text' => 'Enviar',
                'blocks' => [
                    [
                        'id' => 'name-block',
                        'type' => 'text',
                        'label' => 'Nome',
                        'placeholder' => 'Digite seu nome',
                        'required' => true,
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}");

    $page->script(<<<'JS'
        () => {
            window.__openedUrl = null;
            window.__openedTarget = null;
            window.open = (url, target) => {
                window.__openedUrl = url;
                window.__openedTarget = target;

                return null;
            };
        }
    JS);

    $page
        ->fill('input[placeholder="Digite seu nome"]', 'Maria Browser')
        ->click('Enviar')
        ->wait(1)
        ->assertVisible('[data-testid="public-funnel-completed"]')
        ->assertSee('Obrigado, Maria Browser')
        ->assertSee('Seu cadastro foi recebido com sucesso.')
        ->assertVisible('[data-testid="public-completion-primary-button"]')
        ->assertVisible('[data-testid="public-completion-secondary-button"]')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"public-funnel-completed\"] img[alt=\"Imagem da conclusao\"]')?.getAttribute('src') ?? ''"))->toBe('https://example.com/success.png');

    $page->click('[data-testid="public-completion-primary-button"]')
        ->wait(0.2);

    expect($page->script('() => window.__openedUrl'))->toBe('https://example.com');
    expect($page->script('() => window.__openedTarget'))->toBe('_blank');
});

test('public funnel faq can toggle answers open and closed', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil FAQ',
        'slug' => 'funil-faq',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'FAQ',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'faq-block',
                        'type' => 'faq',
                        'label' => '',
                        'required' => false,
                        'faq_first_active' => false,
                        'option_items' => [
                            ['id' => 'faq-1', 'label' => 'Pergunta 1', 'description' => 'Resposta 1', 'points' => 0, 'value' => '', 'destination' => ''],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Pergunta 1');

    expect($page->script("() => document.body.textContent.includes('Resposta 1')"))->toBeFalse();

    $page->click('[data-testid="faq-toggle-faq-block-0"]')
        ->wait(0.5)
        ->assertSee('Resposta 1')
        ->click('[data-testid="faq-toggle-faq-block-0"]')
        ->wait(0.5)
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.body.textContent.includes('Resposta 1')"))->toBeFalse();
});

test('public funnel renders level subtitle without truncation and applies spacer height', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Level Spacer',
        'slug' => 'funil-level-spacer',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Resumo',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'level-block',
                        'type' => 'level',
                        'label' => '',
                        'required' => false,
                        'level_title' => 'Seu progresso',
                        'level_subtitle' => 'Um subtitulo mais longo para garantir que o componente nao corte o texto.',
                        'level_percentage' => 72,
                        'level_indicator_text' => 'Voce esta aqui',
                        'level_legends' => 'Inicio, Meio, Final',
                    ],
                    [
                        'id' => 'spacer-block',
                        'type' => 'spacer',
                        'label' => '',
                        'required' => false,
                        'placeholder' => '48',
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Seu progresso')
        ->assertSee('Um subtitulo mais longo para garantir que o componente nao corte o texto.')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"public-level-subtitle-level-block\"]')?.className.includes('truncate') ?? false"))->toBeFalse();
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-spacer-spacer-block\"]')).height"))->toBe('48px');
});

test('public funnel price redirect opens the configured link in a new tab', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Price Redirect',
        'slug' => 'funil-price-redirect',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Plano',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
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

    $page = visit("/f/{$funnel->slug}");

    $page->script(<<<'JS'
        () => {
            window.__openedUrl = null;
            window.__openedTarget = null;
            window.open = (url, target) => {
                window.__openedUrl = url;
                window.__openedTarget = target;

                return null;
            };
        }
    JS);

    $page->assertSee('Plano Premium')
        ->assertSee('R$ 97')
        ->script("() => document.querySelector('button[title=\"Abrir link do plano\"]')?.click()");

    $page->wait(0.2)
        ->assertNoJavaScriptErrors();

    expect($page->script('() => window.__openedUrl'))->toBe('https://example.com/checkout');
    expect($page->script('() => window.__openedTarget'))->toBe('_blank');
});

test('public funnel testimonials render configured items in grid layout', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Testimonials',
        'slug' => 'funil-testimonials',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Depoimentos',
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
                            [
                                'id' => 'testimonial-2',
                                'label' => 'Carlos',
                                'subtitle' => '@carlos',
                                'description' => 'A conversao melhorou com ajustes simples.',
                                'rating' => 4,
                                'points' => 4,
                                'value' => '@carlos',
                                'destination' => 'A conversao melhorou com ajustes simples.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Marina')
        ->assertSee('@marina')
        ->assertSee('Fechei meu primeiro funil em 3 dias.')
        ->assertSee('Carlos')
        ->assertSee('@carlos')
        ->assertSee('A conversao melhorou com ajustes simples.')
        ->assertNoJavaScriptErrors();
});

test('public funnel carousel pagination changes the visible item', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Carousel',
        'slug' => 'funil-carousel',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Carousel',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'carousel-block',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'carousel_pagination' => true,
                        'option_items' => [
                            ['id' => 'slide-1', 'label' => '', 'description' => 'Descricao 1', 'points' => 0, 'value' => '', 'destination' => ''],
                            ['id' => 'slide-2', 'label' => '', 'description' => 'Descricao 2', 'points' => 0, 'value' => '', 'destination' => ''],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Descricao 1')
        ->click('[data-testid="carousel-dot-carousel-block-1"]')
        ->wait(0.5)
        ->assertSee('Descricao 2')
        ->assertNoJavaScriptErrors();
});

test('public funnel carousel autoplay respects its configured speed', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Carousel Autoplay',
        'slug' => 'funil-carousel-autoplay',
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
                    'id' => 'carousel-autoplay-block',
                    'type' => 'carousel',
                    'label' => '',
                    'required' => false,
                    'carousel_layout' => 'text_only',
                    'carousel_pagination' => true,
                    'carousel_autoplay' => true,
                    'carousel_autoplay_seconds' => 1,
                    'option_items' => [
                        ['id' => 'slide-1', 'label' => '', 'description' => 'Autoplay item 1', 'points' => 0, 'value' => '', 'destination' => ''],
                        ['id' => 'slide-2', 'label' => '', 'description' => 'Autoplay item 2', 'points' => 0, 'value' => '', 'destination' => ''],
                    ],
                ]],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Autoplay item 1')
        ->wait(1.2)
        ->assertSee('Autoplay item 2')
        ->assertNoJavaScriptErrors();
});

test('public funnel text only carousel does not render an empty media frame', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Carousel Texto',
        'slug' => 'funil-carousel-texto',
        'is_active' => true,
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'title' => 'Carousel texto',
                'subtitle' => '',
                'button_text' => '',
                'blocks' => [
                    [
                        'id' => 'carousel-text-only',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'carousel_layout' => 'text_only',
                        'carousel_pagination' => true,
                        'option_items' => [
                            ['id' => 'slide-1', 'label' => '', 'description' => 'Descricao sem imagem', 'points' => 0, 'value' => '', 'destination' => ''],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('Descricao sem imagem')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => !! document.querySelector('[data-testid=\"public-carousel-media-carousel-text-only\"]')"))->toBeFalse();
});

test('public funnel metrics ignore empty items', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Metrics',
        'slug' => 'funil-metrics',
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
                        'id' => 'metrics-block',
                        'type' => 'metrics',
                        'label' => '',
                        'required' => false,
                        'option_items' => [
                            ['id' => 'metric-filled', 'label' => 'Conversao', 'value' => '+32%', 'description' => 'Media das ultimas campanhas', 'points' => 0, 'destination' => ''],
                            ['id' => 'metric-empty', 'label' => '', 'value' => '', 'description' => '', 'points' => 0, 'destination' => ''],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertSee('+32%')
        ->assertSee('Conversao')
        ->assertSee('Media das ultimas campanhas')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelectorAll('[data-testid=\"public-metrics-metrics-block\"] [data-testid^=\"public-metric-metrics-block-\"]').length"))->toBe(1);
});

test('public funnel renders persisted arguments and before after content', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Argumentos Comparativo',
        'slug' => 'funil-argumentos-comparativo',
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
                        'id' => 'arguments-block',
                        'type' => 'arguments',
                        'label' => '',
                        'required' => false,
                        'options' => ['Atendimento consultivo', 'Implantacao acompanhada'],
                    ],
                    [
                        'id' => 'before-after-block',
                        'type' => 'before_after',
                        'label' => '',
                        'required' => false,
                        'options' => ['Processo manual', 'Fluxo automatizado'],
                    ],
                ],
            ],
        ],
    ]);

    visit("/f/{$funnel->slug}")
        ->assertSee('Atendimento consultivo')
        ->assertSee('Implantacao acompanhada')
        ->assertSee('Antes')
        ->assertSee('Processo manual')
        ->assertSee('Depois')
        ->assertSee('Fluxo automatizado')
        ->assertNoJavaScriptErrors();
});

test('public funnel applies semantic design tokens to core components', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Tokens',
        'slug' => 'funil-tokens',
        'is_active' => true,
        'design_settings' => [
            'tokens' => [
                'colors' => [
                    'primary' => '#112233',
                    'heading' => '#aabbcc',
                    'text' => '#ddeeff',
                    'textMuted' => '#778899',
                ],
                'surfaces' => ['page' => '#010203', 'card' => '#040506', 'muted' => '#101820'],
                'borders' => ['default' => '#334455', 'strong' => '#556677', 'focus' => '#778899'],
                'states' => [
                    'success' => '#118844',
                    'warning' => '#cc8800',
                    'danger' => '#cc3344',
                ],
                'components' => [
                    'fieldBackground' => '#121a24',
                    'fieldText' => '#f1f2f3',
                    'primaryButtonBackground' => '#234567',
                    'primaryButtonText' => '#ffffff',
                ],
            ],
        ],
    ]);

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'blocks' => [
                    ['id' => 'token-field', 'type' => 'text', 'label' => 'Nome', 'placeholder' => 'Seu nome', 'required' => false],
                    ['id' => 'token-button', 'type' => 'button', 'label' => 'Continuar', 'required' => false, 'button_color_style' => 'theme'],
                    [
                        'id' => 'token-loading',
                        'type' => 'loading',
                        'label' => 'Carregando',
                        'placeholder' => 'Aguarde',
                        'required' => false,
                        'loading_duration_seconds' => 30,
                    ],
                    [
                        'id' => 'token-testimonials',
                        'type' => 'testimonials',
                        'label' => '',
                        'required' => false,
                        'option_items' => [[
                            'id' => 'token-testimonial',
                            'label' => 'Cliente',
                            'subtitle' => '@cliente',
                            'description' => 'Excelente',
                            'rating' => 5,
                        ]],
                    ],
                    [
                        'id' => 'token-faq',
                        'type' => 'faq',
                        'label' => '',
                        'required' => false,
                        'option_items' => [[
                            'id' => 'token-question',
                            'label' => 'Como funciona?',
                            'description' => 'Assim funciona.',
                        ]],
                    ],
                    [
                        'id' => 'token-metrics',
                        'type' => 'metrics',
                        'label' => '',
                        'required' => false,
                        'option_items' => [[
                            'id' => 'token-metric',
                            'label' => 'Conversao',
                            'value' => '50%',
                            'description' => 'Resultado',
                        ]],
                    ],
                    ['id' => 'token-before-after', 'type' => 'before_after', 'label' => '', 'required' => false, 'options' => ['Antes', 'Depois']],
                    ['id' => 'token-arguments', 'type' => 'arguments', 'label' => '', 'required' => false, 'options' => ['Argumento principal']],
                    [
                        'id' => 'token-price',
                        'type' => 'price',
                        'label' => '',
                        'required' => false,
                        'price_style' => 'theme',
                        'price_title' => 'Plano',
                        'price_value' => 'R$ 99',
                        'price_badge_text' => 'Destaque',
                    ],
                    [
                        'id' => 'token-carousel',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'carousel_layout' => 'text_only',
                        'carousel_border_type' => 'subtle',
                        'option_items' => [[
                            'id' => 'token-slide',
                            'label' => '',
                            'value' => '',
                            'description' => 'Slide tematico',
                        ]],
                    ],
                ],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->assertVisible('[data-testid="public-funnel-card"]')
        ->assertVisible('[data-testid="public-block-button-token-button"]')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => getComputedStyle(document.querySelector('[data-funnel-theme]')).backgroundColor"))->toBe('rgb(1, 2, 3)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-funnel-card\"]')).backgroundColor"))->toBe('rgb(4, 5, 6)');
    expect($page->script("() => getComputedStyle(document.querySelector('input[placeholder=\"Seu nome\"]')).backgroundColor"))->toBe('rgb(18, 26, 36)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-block-button-token-button\"]')).backgroundColor"))->toBe('rgb(35, 69, 103)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-loading-token-loading\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-testimonial-token-testimonials-token-testimonial\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-metric-token-metrics-token-metric\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid^=\"public-before-after-token-before-after-\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-argument-token-arguments-0\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-price-token-price\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-carousel-token-carousel\"]')).backgroundColor"))->toBe('rgb(16, 24, 32)');
    expect($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"public-faq-token-faq-token-question\"] p')).color"))->toBe('rgb(170, 187, 204)');
});

test('public funnel converts youtube shorts links to embed urls', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Video Shorts',
        'slug' => 'funil-video-shorts',
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
                    'id' => 'video-shorts',
                    'type' => 'video',
                    'label' => '',
                    'placeholder' => 'https://www.youtube.com/shorts/kQm_g3DcocA?feature=share',
                    'required' => false,
                ]],
            ],
        ],
    ]);

    $page = visit("/f/{$funnel->slug}")
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('iframe')?.getAttribute('src') ?? ''"))
        ->toBe('https://www.youtube.com/embed/kQm_g3DcocA');
});
