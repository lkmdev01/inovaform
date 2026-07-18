<?php

use App\Models\Funnel;
use App\Models\FunnelSubmission;
use App\Models\FunnelTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $databasePath = database_path('browser-testing.sqlite');

    if (! file_exists($databasePath)) {
        touch($databasePath);
    }

    putenv('DB_CONNECTION=sqlite');
    putenv("DB_DATABASE={$databasePath}");
    putenv('SESSION_DRIVER=file');
    putenv('CACHE_STORE=file');

    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', $databasePath);
    config()->set('session.driver', 'file');
    config()->set('cache.default', 'file');

    DB::purge('sqlite');

    Artisan::call('migrate:fresh', [
        '--force' => true,
    ]);
});

function loginForDashboard(User $user): mixed
{
    return visit('/login')
        ->assertSee('Acessar conta')
        ->type('#email', $user->email)
        ->type('#password', 'password')
        ->press('Entrar no painel')
        ->wait(1);
}

test('dashboard can create a funnel from a selected template', function () {
    $user = User::factory()->create();

    $template = FunnelTemplate::factory()->create([
        'name' => 'Template Browser',
        'slug' => 'template-browser',
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
            ],
            'preview' => [
                'badge' => 'Teste',
                'accentColor' => '#3d8bff',
                'headline' => 'Fluxo pronto para teste no browser.',
                'chips' => ['Lead', 'Quiz'],
            ],
            'stages' => [
                [
                    'name' => 'Etapa 1',
                    'conversion_rate' => 100,
                    'expected_volume' => 1000,
                    'meta' => [
                        'builder' => [
                            'title' => 'Titulo vindo do template',
                            'subtitle' => 'Subtitulo do template',
                            'button_text' => 'Continuar',
                            'blocks' => [
                                [
                                    'id' => 'template-name',
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
                        'builder' => [
                            'title' => 'Etapa final',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    loginForDashboard($user)
        ->click('Criar Funil')
        ->assertVisible('[data-testid="template-card-blank"]')
        ->click("[data-testid=\"template-card-{$template->id}\"]")
        ->click('[data-testid="create-funnel-submit"]')
        ->wait(1)
        ->assertSee('Titulo vindo do template')
        ->assertSee('Subtitulo do template')
        ->assertNoJavaScriptErrors();
});

test('design settings panel scrolls independently within the viewport', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create();

    $page = loginForDashboard($user)
        ->navigate("/funnels/{$funnel->id}/design")
        ->resize(1280, 640)
        ->assertVisible('[data-testid="design-settings-panel"]')
        ->click('PUBLICACAO')
        ->assertSee('Descricao da pagina indisponivel')
        ->assertNoJavaScriptErrors();

    $metrics = $page->script(<<<'JS'
        () => {
            const panel = document.querySelector('[data-testid="design-settings-panel"]');

            if (!(panel instanceof HTMLElement)) {
                return null;
            }

            const initialScrollTop = panel.scrollTop;
            panel.scrollTop = panel.scrollHeight;

            return {
                overflowY: getComputedStyle(panel).overflowY,
                clientHeight: panel.clientHeight,
                scrollHeight: panel.scrollHeight,
                initialScrollTop,
                finalScrollTop: panel.scrollTop,
            };
        }
        JS);

    expect($metrics)->not->toBeNull()
        ->and($metrics['overflowY'])->toBeIn(['auto', 'scroll'])
        ->and($metrics['scrollHeight'])->toBeGreaterThan($metrics['clientHeight'])
        ->and($metrics['finalScrollTop'])->toBeGreaterThan($metrics['initialScrollTop']);
});

test('leads page provides working tabs sharing and lead update feedback', function () {
    $owner = User::factory()->create();
    $sharedUser = User::factory()->create([
        'email' => 'leads.shared@example.com',
    ]);
    $funnel = Funnel::factory()->for($owner)->create([
        'name' => 'Funil Leads Browser',
    ]);
    $firstStage = $funnel->stages()->create([
        'name' => 'Diagnostico',
        'stage_order' => 1,
    ]);
    $funnel->stages()->create([
        'name' => 'Conclusao',
        'stage_order' => 2,
    ]);
    $lead = FunnelSubmission::factory()->for($funnel)->create([
        'status' => 'new',
        'lead_name' => 'Lead Browser',
        'lead_email' => 'lead.browser@example.com',
        'meta' => [
            'priority' => 'normal',
            'tags' => ['teste'],
            'notes' => '',
            'timeline' => [],
        ],
        'submitted_at' => now(),
    ]);
    $lead->answers()->create([
        'funnel_stage_id' => $firstStage->id,
        'block_id' => 'browser-name',
        'block_type' => 'text',
        'block_label' => 'Nome',
        'value' => ['Lead Browser'],
    ]);

    loginForDashboard($owner)
        ->navigate("/funnels/{$funnel->id}/leads")
        ->assertSee('Limpar filtros')
        ->click('[data-testid="leads-tab-results"]')
        ->assertVisible('[data-testid="leads-results-panel"]')
        ->assertSee('Resumo de resultados')
        ->click('[data-testid="leads-tab-performance"]')
        ->assertVisible('[data-testid="leads-performance-panel"]')
        ->assertSee('Desempenho por etapa')
        ->click('[data-testid="leads-tab-responses"]')
        ->assertVisible("[data-testid=\"lead-row-{$lead->id}\"]")
        ->click('[data-testid="leads-share-button"]')
        ->assertSee('Compartilhar funil')
        ->fill('[data-testid="leads-share-email"]', $sharedUser->email)
        ->select('[data-testid="leads-share-role"]', Funnel::SHARE_ROLE_EDITOR)
        ->click('[data-testid="leads-share-submit"]')
        ->wait(1)
        ->assertSee('Compartilhado')
        ->fill("[data-testid=\"lead-tags-{$lead->id}\"]", str_repeat('a', 33))
        ->click("[data-testid=\"lead-update-{$lead->id}\"]")
        ->wait(1)
        ->assertSee('Cada tag deve ter no máximo 32 caracteres.')
        ->fill("[data-testid=\"lead-tags-{$lead->id}\"]", 'teste, browser-validado')
        ->fill("[data-testid=\"lead-notes-{$lead->id}\"]", 'Atualizado no teste de navegador.')
        ->select("[data-testid=\"lead-priority-{$lead->id}\"]", 'high')
        ->select("[data-testid=\"lead-status-{$lead->id}\"]", 'contacted')
        ->click("[data-testid=\"lead-update-{$lead->id}\"]")
        ->wait(1)
        ->assertSee('Lead atualizado com sucesso.')
        ->assertNoJavaScriptErrors();

    expect($funnel->fresh()->sharedUsers()->whereKey($sharedUser->id)->first()?->pivot?->role)
        ->toBe(Funnel::SHARE_ROLE_EDITOR);

    $lead->refresh();

    expect($lead->status)->toBe('contacted');
    expect(data_get($lead->meta, 'priority'))->toBe('high');
    expect(data_get($lead->meta, 'tags'))->toBe(['teste', 'browser-validado']);
    expect(data_get($lead->meta, 'notes'))->toBe('Atualizado no teste de navegador.');
});

test('shared viewer sees restricted leads navigation without a forbidden link', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
        'shared_by_user_id' => $owner->id,
    ]);

    $page = loginForDashboard($viewer);

    foreach (['builder', 'flow', 'design'] as $pageName) {
        $page->navigate("/funnels/{$funnel->id}/{$pageName}")
            ->assertVisible('[data-testid="leads-nav-restricted"]')
            ->assertNoJavaScriptErrors();

        $restrictedNavigation = $page->script(<<<'JS'
            () => {
                const element = document.querySelector('[data-testid="leads-nav-restricted"]');

                return {
                    tagName: element?.tagName,
                    disabled: element instanceof HTMLButtonElement ? element.disabled : false,
                    hasHref: element?.hasAttribute('href') ?? true,
                };
            }
            JS);

        expect($restrictedNavigation)
            ->toMatchArray([
                'tagName' => 'BUTTON',
                'disabled' => true,
                'hasHref' => false,
            ]);
    }

    $this->actingAs($viewer)
        ->get(route('funnels.leads', $funnel))
        ->assertForbidden();
});

test('design offers ready color themes before custom palette controls', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create();

    $page = loginForDashboard($user)
        ->navigate("/funnels/{$funnel->id}/design")
        ->resize(1280, 900)
        ->click('CORES')
        ->assertVisible('[data-testid="color-theme-options"]')
        ->assertVisible('[data-testid="color-theme-inovaform"]')
        ->assertVisible('[data-testid="color-theme-aurora"]')
        ->assertVisible('[data-testid="color-theme-carbon"]')
        ->assertVisible('[data-testid="color-theme-sand"]')
        ->assertVisible('[data-testid="color-theme-custom"]')
        ->assertNoJavaScriptErrors();

    expect($page->script("() => document.querySelector('[data-testid=\"custom-color-controls\"]') === null"))->toBeTrue();

    $page->click('[data-testid="color-theme-aurora"]');

    expect($page->script("() => document.querySelector('[data-testid=\"color-theme-aurora\"]')?.getAttribute('aria-pressed')"))->toBe('true')
        ->and($page->script("() => getComputedStyle(document.querySelector('[data-testid=\"design-preview-theme\"]')).backgroundColor"))->toBe('rgb(30, 16, 56)');

    $page->click('[data-testid="color-theme-custom"]')
        ->assertVisible('[data-testid="custom-color-controls"]')
        ->assertSee('Paleta personalizada')
        ->assertNoJavaScriptErrors();
});

test('publication settings use local timezone and expose publish lifecycle', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'is_active' => false,
    ]);

    $page = loginForDashboard($user)
        ->navigate("/funnels/{$funnel->id}/design")
        ->resize(1440, 900)
        ->assertSee('Rascunho')
        ->click('PUBLICACAO')
        ->assertSee('Não configurado')
        ->assertSee('Ao salvar, será convertido para UTC.');

    $page->click('[data-testid="custom-domain-help-trigger"]')
        ->assertVisible('[data-testid="custom-domain-help-dialog"]')
        ->assertSee('Como conectar seu domínio')
        ->assertSee('CNAME')
        ->assertSee('Não inclua https://')
        ->click('[data-testid="custom-domain-help-close"]')
        ->assertDontSee('Como conectar seu domínio');

    $expectedExpiration = $page->script("() => new Date('2030-01-02T10:30').toISOString()");

    $page->type('[data-testid="publication-expires-at"]', '2030-01-02T10:30')
        ->click('Salvar')
        ->wait(1)
        ->assertSee('Design salvo')
        ->assertNoJavaScriptErrors();

    expect(data_get($funnel->fresh()->design_settings, 'expiresAt'))->toBe($expectedExpiration);

    $page->click('Publicar')
        ->wait(1)
        ->assertSee('Publicado')
        ->assertSee('Funil publicado')
        ->click('Despublicar')
        ->assertSee('Confirmar despublicação')
        ->click('Confirmar despublicação')
        ->wait(1)
        ->assertSee('Rascunho')
        ->assertSee('Funil despublicado')
        ->assertNoJavaScriptErrors();

    expect($funnel->fresh()->is_active)->toBeFalse();
});
