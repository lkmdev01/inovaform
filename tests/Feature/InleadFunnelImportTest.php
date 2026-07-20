<?php

use App\Jobs\RehostImportedFunnelMediaJob;
use App\Models\Funnel;
use App\Models\User;
use App\Support\InleadFunnelImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function inleadImportSource(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 91,
        'title' => 'Funil externo autorizado',
        'description' => 'Conteúdo para conversão',
        'design' => [
            'logo' => [
                'type' => 'image',
                'src' => 'https://media.inlead.cloud/uploads/account/logo.png',
            ],
            'themeColor' => '#16a34a',
            'contentColor' => '#111827',
            'titleColor' => '#030712',
            'backgroundColor' => '#ffffff',
            'featuredFont' => 'inter',
            'rounded' => 'rounded-2xl',
            'elementSize' => '56px',
        ],
        'seo' => [
            'title' => 'SEO externo',
        ],
        'scripts' => [
            'facebook' => '<script>ignored()</script>',
        ],
        'steps' => [
            [
                'id' => 'stage-a',
                'title' => 'Etapa inicial',
                'layers' => [
                    [
                        'type' => 'text',
                        'content' => [
                            'text' => '<h2>Escolha uma opção</h2><script>alert(1)</script>',
                        ],
                    ],
                    [
                        'type' => 'options',
                        'content' => [
                            'required' => true,
                            'cols' => 'grid-cols-2',
                            'options' => [
                                [
                                    'label' => '<p>Continuar</p>',
                                    'destination' => 'stage-b',
                                    'image' => [
                                        'src' => 'https://media.inlead.cloud/uploads/account/choice.png',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'field',
                        'content' => [
                            'type' => 'number',
                            'name' => 'peso_actual',
                            'title' => 'Peso atual',
                            'placeholder' => 'Informe seu peso',
                            'required' => true,
                        ],
                    ],
                ],
            ],
            [
                'id' => 'stage-b',
                'title' => 'Oferta',
                'layers' => [
                    [
                        'type' => 'button',
                        'content' => [
                            'type' => 'redirect',
                            'label' => 'Comprar',
                            'destination' => 'https://pay.hotmart.com/TEST',
                        ],
                    ],
                    [
                        'type' => 'alert',
                        'content' => [
                            'text' => '<p><strong>{{peso_actual}}kg</strong></p><p>Resultado&nbsp;personalizado</p>',
                        ],
                    ],
                    [
                        'type' => 'loading',
                        'content' => [
                            'title' => 'Processando',
                            'description' => '<p><strong>Carregando...</strong></p><p>Estamos&nbsp;preparando</p>',
                            'seconds' => 5,
                        ],
                    ],
                ],
            ],
        ],
    ], $overrides);
}

function inleadPackageUpload(array $source, array $additionalEntries = []): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'inlead-import-');
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('funnel_decrypted.json', json_encode($source, JSON_THROW_ON_ERROR));

    foreach ($additionalEntries as $name => $contents) {
        $archive->addFromString($name, (string) $contents);
    }

    $archive->close();

    return new UploadedFile($path, 'pacote-inlead.zip', 'application/zip', null, true);
}

test('user can safely preview and import an inlead package as a draft', function () {
    Cache::clear();
    $user = User::factory()->create();
    $translation = [
        'funnel_title' => 'Authorized external funnel',
        'seo_title' => 'External SEO',
        'steps' => [
            ['headline' => 'Choose an option', 'options' => ['Continue']],
            ['button' => 'Buy now'],
        ],
    ];
    $upload = inleadPackageUpload(inleadImportSource(), [
        'funnel_translation_neutral.json' => json_encode($translation, JSON_THROW_ON_ERROR),
        'network_log.json' => '{"authorization":"sensitive-value"}',
        '.local/share/pki/nssdb/key4.db' => 'not-a-real-key-database',
    ]);

    try {
        $previewResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('funnels.import.preview'), ['file' => $upload]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('preview.source', 'Pacote Inlead')
            ->assertJsonPath('preview.stage_count', 2)
            ->assertJsonPath('preview.block_count', 6)
            ->assertJsonPath('preview.image_count', 2)
            ->assertJsonPath('preview.default_language', 'original')
            ->assertJsonFragment(['label' => 'Inglês neutro fornecido'])
            ->assertJsonFragment([
                'Arquivos potencialmente sensíveis foram detectados e ignorados: network_log.json, key4.db.',
            ]);
        expect($previewResponse->json('preview.warnings'))
            ->not->toContain('sensitive-value');

        $token = $previewResponse->json('token');
        $importResponse = $this->actingAs($user)->post(route('funnels.import'), [
            'token' => $token,
            'language' => 'english_neutral',
            'name' => 'Funil convertido',
            'copy_media' => false,
        ]);

        $funnel = Funnel::query()->whereBelongsTo($user)->where('name', 'Funil convertido')->firstOrFail();
        $funnel->load(['stages' => static fn ($query) => $query->orderBy('stage_order')]);
        $firstBlocks = $funnel->stages[0]->meta['builder']['blocks'];
        $secondBlocks = $funnel->stages[1]->meta['builder']['blocks'];

        $importResponse->assertRedirect(route('funnels.builder', $funnel));
        expect($funnel->is_active)->toBeFalse()
            ->and($funnel->design_settings['accentColor'])->toBe('#16a34a')
            ->and($firstBlocks[0]['placeholder'])->toContain('Choose an option')
            ->and($firstBlocks[0]['placeholder'])->not->toContain('<script>')
            ->and($firstBlocks[1]['required'])->toBeTrue()
            ->and($firstBlocks[1]['option_items'][0]['destination'])->toBe('2')
            ->and($firstBlocks[1]['option_items'][0]['label'])->toBe('Continue')
            ->and($firstBlocks[2]['variable_name'])->toBe('peso_actual')
            ->and($secondBlocks[0]['button_action'])->toBe('open_link')
            ->and($secondBlocks[0]['button_link'])->toBe('https://pay.hotmart.com/TEST')
            ->and($secondBlocks[1]['placeholder'])->toBe("{{peso_actual}}kg\nResultado personalizado")
            ->and($secondBlocks[2]['placeholder'])->toBe("Carregando...\nEstamos preparando")
            ->and($secondBlocks[1]['placeholder'])->not->toContain('<p>')
            ->and($secondBlocks[2]['placeholder'])->not->toContain('&nbsp;')
            ->and(Cache::has('funnel-import-preview:'.hash('sha256', $token)))->toBeFalse();
    } finally {
        @unlink($upload->getRealPath());
    }
});

test('preview rejects zip packages without converted funnel data', function () {
    $user = User::factory()->create();
    $upload = inleadPackageUpload(inleadImportSource());
    $path = $upload->getRealPath();
    $archive = new ZipArchive;
    $archive->open($path);
    $archive->deleteName('funnel_decrypted.json');
    $archive->addFromString('readme.txt', 'no funnel data');
    $archive->close();

    try {
        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('funnels.import.preview'), ['file' => $upload])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    } finally {
        @unlink($path);
    }
});

test('preview token belongs only to the user who uploaded the package', function () {
    Cache::clear();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = inleadPackageUpload(inleadImportSource());

    try {
        $token = $this->actingAs($owner)
            ->withHeader('Accept', 'application/json')
            ->post(route('funnels.import.preview'), ['file' => $upload])
            ->assertOk()
            ->json('token');

        $this->actingAs($otherUser)
            ->post(route('funnels.import'), [
                'token' => $token,
                'language' => 'original',
                'name' => 'Tentativa indevida',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        expect(Funnel::query()->whereBelongsTo($otherUser)->exists())->toBeFalse();
    } finally {
        @unlink($upload->getRealPath());
    }
});

test('authorized remote images are copied asynchronously after import', function () {
    Cache::clear();
    Queue::fake();
    Storage::fake('public');
    config()->set('inovaform.media.disk', 'public');
    Http::preventStrayRequests();
    Http::fake([
        'https://media.inlead.cloud/*' => Http::response('image-bytes', 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);
    $user = User::factory()->create();
    $upload = inleadPackageUpload(inleadImportSource());

    try {
        $token = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('funnels.import.preview'), ['file' => $upload])
            ->assertOk()
            ->json('token');

        $response = $this->actingAs($user)->post(route('funnels.import'), [
            'token' => $token,
            'language' => 'original',
            'name' => 'Funil com mídia local',
            'copy_media' => true,
        ]);

        $funnel = Funnel::query()->whereBelongsTo($user)->where('name', 'Funil com mídia local')->firstOrFail();
        $response
            ->assertRedirect(route('funnels.builder', $funnel))
            ->assertSessionHas('status', 'funnel-imported-media-queued');

        expect($funnel->fresh()->design_settings['importMedia']['status'])->toBe('queued')
            ->and($funnel->fresh()->design_settings['importMedia']['total'])->toBe(2);
        Http::assertNothingSent();
        Queue::assertPushed(RehostImportedFunnelMediaJob::class, function (RehostImportedFunnelMediaJob $job) use ($funnel): bool {
            return $job->funnelId === $funnel->id && $job->connection === 'deferred';
        });

        $settings = $funnel->fresh()->design_settings;
        $settings['seoTitle'] = 'Título editado enquanto aguardava';
        $funnel->update(['design_settings' => $settings]);

        (new RehostImportedFunnelMediaJob($funnel->id))->handle(app(InleadFunnelImporter::class));

        $funnel->refresh();
        $funnel->load(['stages' => static fn ($query) => $query->orderBy('stage_order')]);
        $imageUrl = $funnel->stages[0]->meta['builder']['blocks'][1]['option_items'][0]['image_url'];

        expect($funnel->design_settings['logoUrl'])->toStartWith('/media/funnels/'.$funnel->id.'/media/image/import-')
            ->and($imageUrl)->toStartWith('/media/funnels/'.$funnel->id.'/media/image/import-')
            ->and($funnel->design_settings['seoTitle'])->toBe('Título editado enquanto aguardava')
            ->and($funnel->design_settings['importMedia']['status'])->toBe('completed')
            ->and($funnel->design_settings['importMedia']['imported'])->toBe(2)
            ->and($funnel->design_settings['importMedia']['failed'])->toBe(0)
            ->and(Storage::disk('public')->allFiles("funnels/{$funnel->id}/media/image"))->toHaveCount(2);
    } finally {
        @unlink($upload->getRealPath());
    }
});
