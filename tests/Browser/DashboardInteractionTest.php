<?php

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
