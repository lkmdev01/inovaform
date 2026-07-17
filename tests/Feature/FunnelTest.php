<?php

use App\Http\Controllers\FunnelController;
use App\Http\Requests\UpdateFunnelRequest;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\FunnelTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected when accessing funnel routes', function () {
    $this->get(route('funnels.index'))->assertRedirect(route('login'));
    $funnel = Funnel::factory()->create();

    $this->get(route('funnels.builder', $funnel))->assertRedirect(route('login'));
    $this->get(route('funnels.design', $funnel))->assertRedirect(route('login'));
    $this->get(route('funnels.leads', $funnel))->assertRedirect(route('login'));

    $this->post(route('funnels.store'), [
        'name' => 'Funil SaaS',
        'stages' => [
            ['name' => 'Visitantes'],
            ['name' => 'Clientes'],
        ],
    ])->assertRedirect(route('login'));

    $this->post(route('funnels.share', $funnel), [
        'email' => 'test@example.com',
    ])->assertRedirect(route('login'));

    $this->delete(route('funnels.destroy', $funnel))->assertRedirect(route('login'));
});

test('authenticated users can view funnels', function () {
    $user = User::factory()->create();

    Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Funil de Conversao',
        ]);

    $this->actingAs($user)
        ->get(route('funnels.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Index')
            ->has('funnels', 1)
            ->where('funnels.0.name', 'Funil de Conversao')
            ->has('funnels.0.stages', 3)
        );
});

test('authenticated users can store funnels with stages', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('funnels.store'), [
            'name' => 'Funil Produto A',
            'description' => 'Fluxo comercial do produto A.',
            'target_leads' => 1200,
            'is_active' => true,
            'stages' => [
                [
                    'name' => 'Visitantes',
                    'conversion_rate' => 100,
                    'expected_volume' => 5000,
                ],
                [
                    'name' => 'Leads',
                    'conversion_rate' => 35,
                    'expected_volume' => 1750,
                ],
                [
                    'name' => 'Clientes',
                    'conversion_rate' => 18,
                    'expected_volume' => 315,
                ],
            ],
        ]);

    $this->assertDatabaseHas('funnels', [
        'user_id' => $user->id,
        'name' => 'Funil Produto A',
    ]);

    $funnel = Funnel::query()->where('name', 'Funil Produto A')->firstOrFail();
    $response->assertRedirect(route('funnels.builder', $funnel));

    expect($funnel->stages()->count())->toBe(3);
});

test('funnel owner can delete a funnel', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();

    $response = $this->actingAs($user)->delete(route('funnels.destroy', $funnel));

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseMissing('funnels', [
        'id' => $funnel->id,
    ]);
});

test('shared editor cannot delete a funnel', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create();
    $funnel->sharedUsers()->attach($editor->id, [
        'role' => Funnel::SHARE_ROLE_EDITOR,
        'shared_by_user_id' => $owner->id,
    ]);

    $this->actingAs($editor)
        ->delete(route('funnels.destroy', $funnel))
        ->assertForbidden();

    $this->assertDatabaseHas('funnels', [
        'id' => $funnel->id,
    ]);
});

test('authenticated users can store funnels from a template', function () {
    $user = User::factory()->create();
    $template = FunnelTemplate::factory()->create([
        'name' => 'Diagnostico Express',
        'slug' => 'diagnostico-express',
        'description' => 'Template base de diagnostico.',
        'schema' => [
            'target_leads' => 1600,
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
            'stages' => [
                [
                    'name' => 'Captura',
                    'conversion_rate' => 100,
                    'expected_volume' => 3000,
                    'meta' => [
                        'builder' => [
                            'title' => 'Titulo do template',
                            'blocks' => [
                                [
                                    'id' => 'template-email',
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
                    'name' => 'Contato',
                    'conversion_rate' => 30,
                    'expected_volume' => 900,
                    'meta' => [
                        'builder' => [
                            'title' => 'Ultima etapa',
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->actingAs($user)
        ->post(route('funnels.store'), [
            'name' => 'Funil via Template',
            'template_id' => $template->id,
            'description' => 'Criado a partir do modal',
            'is_active' => true,
        ]);

    $response->assertSessionHasNoErrors();
    expect(Funnel::query()->pluck('name')->all())->toContain('Funil via Template');

    $funnel = Funnel::query()->where('name', 'Funil via Template')->firstOrFail();

    $response->assertRedirect(route('funnels.builder', $funnel));

    expect($funnel->target_leads)->toBe(1600);
    expect($funnel->design_settings)->toMatchArray($template->schema['design_settings']);
    expect($funnel->stages()->count())->toBe(2);
    expect($funnel->stages()->orderBy('stage_order')->first()?->meta['builder']['title'])->toBe('Titulo do template');
});

test('funnel owner can duplicate a funnel complete', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil Original',
        'slug' => 'funil-original',
        'custom_domain' => 'quiz.original.com',
        'design_settings' => [
            'alignment' => 'left',
            'seoTitle' => 'Titulo SEO',
        ],
    ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => ['builder' => ['blocks' => [['id' => 'text-1', 'type' => 'text', 'label' => 'Nome', 'required' => true]]]],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => ['builder' => ['blocks' => [['id' => 'button-1', 'type' => 'button', 'label' => 'Continuar', 'required' => false]]]],
        ],
    ]);

    $response = $this->actingAs($user)->post(route('funnels.duplicate', $funnel));

    $duplicate = Funnel::query()
        ->whereKeyNot($funnel->id)
        ->latest('id')
        ->firstOrFail();

    $response->assertRedirect(route('funnels.builder', $duplicate));
    expect($duplicate->name)->toBe('Funil Original Copia');
    expect($duplicate->slug)->not->toBe($funnel->slug);
    expect($duplicate->custom_domain)->toBeNull();
    expect($duplicate->is_active)->toBeFalse();
    expect($duplicate->stages()->count())->toBe(2);
    expect($duplicate->design_settings['seoTitle'])->toBe('Titulo SEO');
});

test('funnel owner can export a funnel as json', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil Exportavel',
        'slug' => 'funil-exportavel',
        'design_settings' => [
            'seoTitle' => 'SEO Exportado',
        ],
    ]);
    $funnel->stages()->createMany([
        ['name' => 'Etapa 1', 'stage_order' => 1, 'meta' => ['builder' => ['blocks' => []]]],
        ['name' => 'Etapa 2', 'stage_order' => 2, 'meta' => ['builder' => ['blocks' => []]]],
    ]);

    $response = $this->actingAs($user)->get(route('funnels.export', $funnel));

    $response->assertOk();
    $payload = json_decode($response->streamedContent(), true);

    expect($payload['schema_version'])->toBe(1);
    expect($payload['funnel']['name'])->toBe('Funil Exportavel');
    expect($payload['funnel']['design_settings']['seoTitle'])->toBe('SEO Exportado');
    expect($payload['funnel']['stages'])->toHaveCount(2);
});

test('authenticated user can import a funnel from json', function () {
    $user = User::factory()->create();
    $payload = [
        'schema_version' => 1,
        'funnel' => [
            'name' => 'Funil Importado',
            'description' => 'Importado do JSON',
            'target_leads' => 900,
            'is_active' => true,
            'design_settings' => [
                'seoTitle' => 'SEO importado',
                'logoUrl' => 'https://example.com/logo.png',
            ],
            'stages' => [
                ['name' => 'Etapa 1', 'meta' => ['builder' => ['blocks' => [['id' => 'text-1', 'type' => 'text', 'label' => 'Nome', 'required' => true]]]]],
                ['name' => 'Etapa 2', 'meta' => ['builder' => ['blocks' => []]]],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post(route('funnels.import'), [
        'file' => UploadedFile::fake()->createWithContent('funil.json', json_encode($payload, JSON_THROW_ON_ERROR)),
    ]);

    $funnel = Funnel::query()->where('name', 'Funil Importado')->firstOrFail();

    $response->assertRedirect(route('funnels.builder', $funnel));
    expect($funnel->is_active)->toBeFalse();
    expect($funnel->design_settings['seoTitle'])->toBe('SEO importado');
    expect($funnel->stages()->count())->toBe(2);
});

test('funnel owner can save a funnel as a versioned user template', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil Template',
        'description' => 'Descricao base',
        'design_settings' => [
            'accentColor' => '#123456',
        ],
    ]);
    $funnel->stages()->createMany([
        ['name' => 'Etapa 1', 'stage_order' => 1, 'meta' => ['builder' => ['title' => '', 'blocks' => [[
            'id' => 'hero-copy',
            'type' => 'content_text',
            'label' => '',
            'placeholder' => '<h2>Titulo do template</h2><p>Descricao do template</p>',
            'required' => false,
        ]]]]],
        ['name' => 'Etapa 2', 'stage_order' => 2, 'meta' => ['builder' => ['blocks' => []]]],
    ]);

    $this->actingAs($user)->post(route('funnels.templates.store', $funnel), [
        'name' => 'Template Comercial',
        'description' => 'Versao 1',
        'category' => 'captacao',
        'thumbnail_path' => 'https://example.com/thumb.png',
        'is_premium' => false,
    ])->assertRedirect();

    $this->actingAs($user)->post(route('funnels.templates.store', $funnel), [
        'name' => 'Template Comercial',
        'description' => 'Versao 2',
        'category' => 'captacao',
        'thumbnail_path' => 'https://example.com/thumb-v2.png',
        'is_premium' => true,
    ])->assertRedirect();

    $templates = FunnelTemplate::query()
        ->where('user_id', $user->id)
        ->where('source_funnel_id', $funnel->id)
        ->orderBy('version')
        ->get();

    expect($templates)->toHaveCount(2);
    expect($templates[0]->version)->toBe(1);
    expect($templates[1]->version)->toBe(2);
    expect($templates[1]->is_premium)->toBeTrue();
    expect($templates[1]->schema['preview']['headline'])->toBe('Titulo do template Descricao do template');
});

test('funnel editor can upload media files', function () {
    config()->set('inovaform.media.disk', 'public');

    Storage::fake('public');

    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('funnels.media.upload', $funnel), [
        'kind' => 'image',
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure(['url', 'path', 'kind', 'disk'])
        ->assertJsonPath('kind', 'image')
        ->assertJsonPath('disk', 'public');

    expect((string) $response->json('url'))->toStartWith('/media/funnels/');

    $storedPath = (string) $response->json('path');
    Storage::disk('public')->assertExists($storedPath);
    expect(Storage::disk('public')->path($storedPath))->toBeFile();
});

test('funnel editor can upload images and audio to the configured r2 disk', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('public');
    Storage::fake('r2');

    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create();

    $imageResponse = $this->actingAs($user)->post(route('funnels.media.upload', $funnel), [
        'kind' => 'image',
        'file' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $imageResponse
        ->assertOk()
        ->assertJsonPath('kind', 'image')
        ->assertJsonPath('disk', 'r2');

    expect((string) $imageResponse->json('url'))->toStartWith('https://cdn.example.com/funnels/');

    $imagePath = (string) $imageResponse->json('path');
    Storage::disk('r2')->assertExists($imagePath);
    Storage::disk('public')->assertMissing($imagePath);

    $audioResponse = $this->actingAs($user)->post(route('funnels.media.upload', $funnel), [
        'kind' => 'audio',
        'file' => UploadedFile::fake()->create('audio.mp3', 50, 'audio/mpeg'),
    ]);

    $audioResponse
        ->assertOk()
        ->assertJsonPath('kind', 'audio')
        ->assertJsonPath('disk', 'r2');

    $audioPath = (string) $audioResponse->json('path');
    expect((string) $audioResponse->json('url'))->toStartWith('https://cdn.example.com/funnels/');
    Storage::disk('r2')->assertExists($audioPath);
    Storage::disk('public')->assertMissing($audioPath);
});

test('funnel owner update removes orphaned managed media removed from builder blocks on r2', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('r2');

    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil Midia R2',
        'is_active' => false,
    ]);

    $imagePath = "funnels/{$funnel->id}/media/image/old-block-image.png";
    $notificationAvatarPath = "funnels/{$funnel->id}/media/image/old-notification-avatar.png";
    $imageUrl = "https://cdn.example.com/{$imagePath}";
    $notificationAvatarUrl = "https://cdn.example.com/{$notificationAvatarPath}";

    Storage::disk('r2')->put($imagePath, 'old-image');
    Storage::disk('r2')->put($notificationAvatarPath, 'old-avatar');

    $firstStage = $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'blocks' => [
                    [
                        'id' => 'image-block',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => $imageUrl,
                        'required' => false,
                    ],
                    [
                        'id' => 'notification-block',
                        'type' => 'notification',
                        'label' => '',
                        'notification_title' => '@1',
                        'notification_description' => '@2',
                        'notification_avatar_url' => $notificationAvatarUrl,
                        'notification_variations' => [],
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $secondStage = $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => [
            'builder' => [
                'blocks' => [],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Midia R2',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $firstStage->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
                [
                    'id' => $secondStage->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    Storage::disk('r2')->assertMissing($imagePath);
    Storage::disk('r2')->assertMissing($notificationAvatarPath);
});

test('funnel owner update removes orphaned managed audio and option media removed from builder blocks on r2', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('r2');

    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil Midia Complementar R2',
        'is_active' => false,
    ]);

    $audioPath = "funnels/{$funnel->id}/media/audio/original-audio.mp3";
    $audioAvatarPath = "funnels/{$funnel->id}/media/audio/original-avatar.png";
    $optionImagePath = "funnels/{$funnel->id}/media/image/original-option.png";
    $carouselImagePath = "funnels/{$funnel->id}/media/image/original-carousel.png";

    Storage::disk('r2')->put($audioPath, 'audio');
    Storage::disk('r2')->put($audioAvatarPath, 'avatar');
    Storage::disk('r2')->put($optionImagePath, 'option-image');
    Storage::disk('r2')->put($carouselImagePath, 'carousel-image');

    $firstStage = $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'blocks' => [
                    [
                        'id' => 'audio-block',
                        'type' => 'audio',
                        'label' => '',
                        'audio_src' => "https://cdn.example.com/{$audioPath}",
                        'audio_avatar_url' => "https://cdn.example.com/{$audioAvatarPath}",
                        'required' => false,
                    ],
                    [
                        'id' => 'single-choice-block',
                        'type' => 'single_choice',
                        'label' => '',
                        'required' => false,
                        'option_items' => [
                            [
                                'id' => 'option-1',
                                'label' => 'Opcao com imagem',
                                'points' => 1,
                                'value' => 'A',
                                'destination' => 'next_stage',
                                'image_url' => "https://cdn.example.com/{$optionImagePath}",
                            ],
                        ],
                    ],
                    [
                        'id' => 'carousel-block',
                        'type' => 'carousel',
                        'label' => '',
                        'required' => false,
                        'option_items' => [
                            [
                                'id' => 'carousel-1',
                                'label' => '',
                                'value' => "https://cdn.example.com/{$carouselImagePath}",
                                'description' => 'Imagem principal',
                                'destination' => '',
                                'points' => 0,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $secondStage = $funnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => [
            'builder' => [
                'blocks' => [],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Midia Complementar R2',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $firstStage->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
                [
                    'id' => $secondStage->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    Storage::disk('r2')->assertMissing($audioPath);
    Storage::disk('r2')->assertMissing($audioAvatarPath);
    Storage::disk('r2')->assertMissing($optionImagePath);
    Storage::disk('r2')->assertMissing($carouselImagePath);
});

test('funnel owner update keeps managed media still referenced by another funnel on r2', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('r2');

    $user = User::factory()->create();
    $sharedPath = 'funnels/shared/media/image/reused-image.png';
    $sharedUrl = "https://cdn.example.com/{$sharedPath}";

    Storage::disk('r2')->put($sharedPath, 'shared-image');

    $firstFunnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil A',
        'is_active' => false,
    ]);

    $firstStage = $firstFunnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'blocks' => [
                    [
                        'id' => 'image-block-a',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => $sharedUrl,
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $firstFunnelSecondStage = $firstFunnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => [
            'builder' => [
                'blocks' => [],
            ],
        ],
    ]);

    $secondFunnel = Funnel::factory()->for($user)->create([
        'name' => 'Funil B',
        'is_active' => false,
    ]);

    $secondFunnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'blocks' => [
                    [
                        'id' => 'image-block-b',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => $sharedUrl,
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $secondFunnel->stages()->create([
        'name' => 'Etapa 2',
        'stage_order' => 2,
        'meta' => [
            'builder' => [
                'blocks' => [],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->patch(route('funnels.update', $firstFunnel), [
            'name' => 'Funil A',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $firstStage->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
                [
                    'id' => $firstFunnelSecondStage->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $firstFunnel));

    Storage::disk('r2')->assertExists($sharedPath);
});

test('funnel owner deleting a funnel removes orphaned managed media from r2', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('r2');

    $user = User::factory()->create();
    $funnel = Funnel::factory()->for($user)->create();
    $imagePath = "funnels/{$funnel->id}/media/image/delete-on-destroy.png";
    $imageUrl = "https://cdn.example.com/{$imagePath}";

    Storage::disk('r2')->put($imagePath, 'destroy-image');

    $funnel->stages()->create([
        'name' => 'Etapa 1',
        'stage_order' => 1,
        'meta' => [
            'builder' => [
                'blocks' => [
                    [
                        'id' => 'destroy-image-block',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => $imageUrl,
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
                'blocks' => [],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->delete(route('funnels.destroy', $funnel))
        ->assertRedirect(route('dashboard'));

    Storage::disk('r2')->assertMissing($imagePath);
});

test('user cannot upload media to funnel without edit permission', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->post(route('funnels.media.upload', $funnel), [
            'kind' => 'audio',
            'file' => UploadedFile::fake()->create('voice.mp3', 200, 'audio/mpeg'),
        ])
        ->assertForbidden();
});

test('funnel owner can update funnel from builder', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Funil Antigo',
            'is_active' => false,
        ]);

    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Atualizado',
            'is_active' => true,
            'stages' => [
                [
                    'id' => $stages[1]->id,
                    'name' => 'Nova Etapa 2',
                    'conversion_rate' => 41,
                    'expected_volume' => 820,
                    'meta' => [
                        'header' => [
                            'show_logo' => false,
                            'show_progress' => true,
                            'allow_back' => false,
                        ],
                    ],
                ],
                [
                    'id' => $stages[0]->id,
                    'name' => 'Nova Etapa 1',
                    'conversion_rate' => 100,
                    'expected_volume' => 2500,
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => false,
                            'allow_back' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'Etapa Nova',
                    'conversion_rate' => 15,
                    'expected_volume' => 120,
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    $this->assertDatabaseHas('funnels', [
        'id' => $funnel->id,
        'name' => 'Funil Atualizado',
        'is_active' => true,
    ]);

    expect($funnel->fresh()->stages()->count())->toBe(3);

    $orderedStages = $funnel->fresh()->stages()->orderBy('stage_order')->get();

    expect($orderedStages[0]->id)->toBe($stages[1]->id);
    expect($orderedStages[0]->name)->toBe('Nova Etapa 2');
    expect($orderedStages[1]->id)->toBe($stages[0]->id);
    expect($orderedStages[1]->name)->toBe('Nova Etapa 1');
    expect($orderedStages[2]->name)->toBe('Etapa Nova');

    $this->assertDatabaseMissing('funnel_stages', [
        'id' => $stages[2]->id,
    ]);
});

test('funnel owner can save stage builder blocks', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Builder Blocks',
            'is_active' => true,
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => 'Titulo da etapa',
                            'subtitle' => 'Subtitulo da etapa',
                            'button_text' => 'Proxima etapa',
                            'blocks' => [
                                [
                                    'id' => 'block-1',
                                    'type' => 'text',
                                    'label' => 'Seu nome',
                                    'placeholder' => 'Digite seu nome',
                                    'required' => true,
                                ],
                                [
                                    'id' => 'block-2',
                                    'type' => 'phone',
                                    'label' => 'Celular',
                                    'placeholder' => 'Digite seu celular',
                                    'required' => false,
                                    'phone_mask' => 'us',
                                ],
                                [
                                    'id' => 'block-3',
                                    'type' => 'button',
                                    'label' => 'Continuar',
                                    'required' => false,
                                    'button_action' => 'next_stage',
                                    'button_target_stage_order' => '2',
                                    'button_color_style' => 'theme',
                                    'button_animated' => true,
                                    'button_elevated' => true,
                                    'button_sticky_footer' => false,
                                ],
                                [
                                    'id' => 'block-4',
                                    'type' => 'number',
                                    'label' => 'Orcamento',
                                    'placeholder' => 'Digite um valor',
                                    'required' => false,
                                    'number_mask' => 'euro',
                                    'show_after_seconds' => 4,
                                    'display_rule_mode' => 'any',
                                    'display_rules' => [
                                        [
                                            'id' => 'rule-1',
                                            'source_block_id' => 'block-1',
                                            'operator' => 'filled',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'block-5',
                                    'type' => 'height',
                                    'label' => 'Altura',
                                    'required' => false,
                                    'height_mode' => 'ruler',
                                ],
                                [
                                    'id' => 'block-6',
                                    'type' => 'weight',
                                    'label' => 'Peso',
                                    'required' => false,
                                    'weight_mode' => 'ruler',
                                ],
                                [
                                    'id' => 'block-7',
                                    'type' => 'single_choice',
                                    'label' => 'Plano atual',
                                    'required' => false,
                                    'options' => ['Basico', 'Pro'],
                                ],
                                [
                                    'id' => 'block-8',
                                    'type' => 'audio',
                                    'label' => 'Audio',
                                    'required' => false,
                                    'audio_sender' => 'Joao Silva',
                                    'audio_src' => 'https://example.com/audio.mp3',
                                    'audio_avatar_url' => 'https://example.com/avatar.jpg',
                                    'audio_model' => 'whatsapp',
                                    'audio_theme' => 'light',
                                ],
                                [
                                    'id' => 'block-9',
                                    'type' => 'metrics',
                                    'label' => 'Metricas',
                                    'required' => false,
                                    'options' => ['Taxa de resposta', 'Tempo medio', 'Satisfacao'],
                                    'option_items' => [
                                        [
                                            'id' => 'metric-1',
                                            'label' => 'Taxa de resposta',
                                            'points' => 0,
                                            'value' => '+32%',
                                            'destination' => 'vs ultimo ciclo',
                                            'description' => 'vs ultimo ciclo',
                                        ],
                                        [
                                            'id' => 'metric-2',
                                            'label' => 'Tempo medio',
                                            'points' => 0,
                                            'value' => '48h',
                                            'destination' => 'entre entrada e contato',
                                            'description' => 'entre entrada e contato',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    $updatedStage = $stages[0]->fresh();

    expect($updatedStage->meta['builder']['title'])->toBe('Titulo da etapa');
    expect($updatedStage->meta['builder']['button_text'])->toBe('Proxima etapa');
    expect($updatedStage->meta['builder']['blocks'])->toHaveCount(9);
    expect($updatedStage->meta['builder']['blocks'][1]['type'])->toBe('phone');
    expect($updatedStage->meta['builder']['blocks'][1]['phone_mask'])->toBe('us');
    expect($updatedStage->meta['builder']['blocks'][2]['type'])->toBe('button');
    expect($updatedStage->meta['builder']['blocks'][2]['button_target_stage_order'])->toBe('2');
    expect($updatedStage->meta['builder']['blocks'][2]['button_animated'])->toBeTrue();
    expect($updatedStage->meta['builder']['blocks'][3]['type'])->toBe('number');
    expect($updatedStage->meta['builder']['blocks'][3]['number_mask'])->toBe('euro');
    expect($updatedStage->meta['builder']['blocks'][3]['show_after_seconds'])->toBe(4);
    expect($updatedStage->meta['builder']['blocks'][3]['display_rule_mode'])->toBe('any');
    expect($updatedStage->meta['builder']['blocks'][3]['display_rules'][0]['source_block_id'])->toBe('block-1');
    expect($updatedStage->meta['builder']['blocks'][3]['display_rules'][0]['operator'])->toBe('filled');
    expect($updatedStage->meta['builder']['blocks'][4]['type'])->toBe('height');
    expect($updatedStage->meta['builder']['blocks'][4]['height_mode'])->toBe('ruler');
    expect($updatedStage->meta['builder']['blocks'][5]['type'])->toBe('weight');
    expect($updatedStage->meta['builder']['blocks'][5]['weight_mode'])->toBe('ruler');
    expect($updatedStage->meta['builder']['blocks'][6]['type'])->toBe('single_choice');
    expect($updatedStage->meta['builder']['blocks'][7]['type'])->toBe('audio');
    expect($updatedStage->meta['builder']['blocks'][7]['audio_sender'])->toBe('Joao Silva');
    expect($updatedStage->meta['builder']['blocks'][8]['type'])->toBe('metrics');
    expect($updatedStage->meta['builder']['blocks'][8]['option_items'][0]['value'])->toBe('+32%');
    expect($updatedStage->meta['builder']['blocks'][8]['option_items'][1]['destination'])->toBe('entre entrada e contato');
});

test('funnel owner can save notification and timer texts', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Timer Notification',
            'is_active' => true,
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [
                                [
                                    'id' => 'notification-block',
                                    'type' => 'notification',
                                    'label' => '',
                                    'placeholder' => '',
                                    'required' => false,
                                    'notification_title' => 'Maria acabou de entrar',
                                    'notification_description' => 'Receba seu diagnostico em instantes',
                                    'notification_avatar_url' => '@4',
                                    'notification_position' => 'default',
                                    'notification_duration_seconds' => 5,
                                    'notification_interval_seconds' => 2,
                                    'notification_style' => 'white',
                                    'notification_size' => 'large',
                                    'notification_variant' => 'social',
                                    'notification_variations' => [
                                        [
                                            'id' => 'variation-1',
                                            'value1' => 'Maria',
                                            'value2' => 'Instagram',
                                            'value3' => '7',
                                            'value4' => 'https://cdn.example.com/avatar-maria.png',
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'timer-block',
                                    'type' => 'timer',
                                    'label' => '',
                                    'placeholder' => '',
                                    'required' => false,
                                    'timer_seconds' => 18,
                                    'timer_text' => 'Oferta expira em [time]',
                                    'timer_style' => 'red',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    $updatedStage = $stages[0]->fresh();

    expect($updatedStage->meta['builder']['blocks'])->toHaveCount(2);
    expect($updatedStage->meta['builder']['blocks'][0]['notification_title'])->toBe('Maria acabou de entrar');
    expect($updatedStage->meta['builder']['blocks'][0]['notification_description'])->toBe('Receba seu diagnostico em instantes');
    expect($updatedStage->meta['builder']['blocks'][0]['notification_avatar_url'])->toBe('@4');
    expect($updatedStage->meta['builder']['blocks'][0]['notification_size'])->toBe('large');
    expect($updatedStage->meta['builder']['blocks'][0]['notification_variant'])->toBe('social');
    expect($updatedStage->meta['builder']['blocks'][0]['notification_variations'][0]['value1'])->toBe('Maria');
    expect($updatedStage->meta['builder']['blocks'][0]['notification_variations'][0]['value4'])->toBe('https://cdn.example.com/avatar-maria.png');
    expect($updatedStage->meta['builder']['blocks'][1]['timer_seconds'])->toBe(18);
    expect($updatedStage->meta['builder']['blocks'][1]['timer_text'])->toBe('Oferta expira em [time]');
    expect($updatedStage->meta['builder']['blocks'][1]['timer_style'])->toBe('red');
});

test('funnel owner can save long content text and carousel image urls', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();
    $richText = str_repeat('<p>Conteudo rico para validar o salvamento do editor.</p>', 12);
    $carouselImageUrl = '/media/funnels/1/media/image/xgihEuimnBLZmh7u6GaBy9FtzITBRE7fTUMnJFDy.png';

    $response = $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Conteudo Longo',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [
                                [
                                    'id' => 'content-block',
                                    'type' => 'content_text',
                                    'label' => '',
                                    'placeholder' => $richText,
                                    'required' => false,
                                ],
                                [
                                    'id' => 'carousel-block',
                                    'type' => 'carousel',
                                    'label' => '',
                                    'required' => false,
                                    'option_items' => [
                                        [
                                            'id' => 'carousel-item-1',
                                            'label' => '',
                                            'points' => 0,
                                            'value' => $carouselImageUrl,
                                            'image_url' => $carouselImageUrl,
                                            'destination' => '',
                                            'description' => 'Slide principal',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('funnels.builder', $funnel));

    $updatedStage = $stages[0]->fresh();

    expect($updatedStage->meta['builder']['blocks'][0]['placeholder'])->toBe($richText);
    expect($updatedStage->meta['builder']['blocks'][1]['option_items'][0]['value'])->toBe($carouselImageUrl);
    expect($updatedStage->meta['builder']['blocks'][1]['option_items'][0]['image_url'])->toBe($carouselImageUrl);
});

test('funnel owner receives a controlled error when builder save fails unexpectedly', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get()->values();
    $payload = [
        'name' => 'Funil com falha controlada',
        'is_active' => false,
        'stages' => [
            [
                'id' => $stages[0]->id,
                'name' => 'Etapa 1',
                'meta' => [
                    'header' => [
                        'show_logo' => true,
                        'show_progress' => true,
                        'allow_back' => true,
                    ],
                    'builder' => [
                        'title' => '',
                        'subtitle' => '',
                        'button_text' => '',
                        'blocks' => [],
                    ],
                ],
            ],
            [
                'id' => $stages[1]->id,
                'name' => 'Etapa 2',
                'meta' => [
                    'header' => [
                        'show_logo' => true,
                        'show_progress' => true,
                        'allow_back' => true,
                    ],
                    'builder' => [
                        'title' => '',
                        'subtitle' => '',
                        'button_text' => '',
                        'blocks' => [],
                    ],
                ],
            ],
        ],
    ];

    $request = new class($payload) extends UpdateFunnelRequest
    {
        /**
         * @param  array<string, mixed>  $payload
         */
        public function __construct(private array $payload)
        {
            parent::__construct();
        }

        /**
         * @return array<string, mixed>
         */
        public function validated($key = null, $default = null): array
        {
            return $this->payload;
        }
    };

    $session = app('session.store');
    $session->start();
    $session->put('_previous.url', route('funnels.builder', $funnel));

    $request->setLaravelSession($session);
    $request->setUserResolver(static fn () => $user);
    $request->setRouteResolver(static function () use ($funnel): LaravelRoute {
        $route = new LaravelRoute('PATCH', '/funnels/{funnel}', []);
        $route->setParameter('funnel', $funnel);

        return $route;
    });

    DB::shouldReceive('transaction')
        ->once()
        ->andThrow(new RuntimeException('falha simulada no salvamento'));

    $response = app(FunnelController::class)->update($request, $funnel);

    expect($response->getTargetUrl())->toBe(route('funnels.builder', $funnel));
    expect($session->get('errors')->get('save'))->toBe([
        'Nao foi possivel salvar o funil agora. Tente novamente em instantes.',
    ]);
});

test('funnel owner can save builder draft with empty labels and options', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Draft Vazio',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [
                                [
                                    'id' => 'draft-button',
                                    'type' => 'button',
                                    'label' => '',
                                    'required' => false,
                                    'button_action' => 'next_stage',
                                    'button_target_stage_order' => 'next',
                                ],
                                [
                                    'id' => 'draft-options',
                                    'type' => 'single_choice',
                                    'label' => '',
                                    'required' => false,
                                    'options' => [''],
                                    'option_items' => [
                                        [
                                            'id' => 'draft-option-1',
                                            'label' => '',
                                            'points' => 0,
                                            'value' => 'A',
                                            'destination' => 'next_stage',
                                        ],
                                    ],
                                    'options_intro_type' => 'none',
                                    'options_intro_title' => '',
                                    'options_intro_description' => '',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    $updatedStage = $stages[0]->fresh();

    expect($updatedStage->meta['builder']['title'])->toBeNull();
    expect($updatedStage->meta['builder']['subtitle'])->toBeNull();
    expect($updatedStage->meta['builder']['button_text'])->toBeNull();
    expect($updatedStage->meta['builder']['blocks'][0]['label'])->toBeNull();
    expect($updatedStage->meta['builder']['blocks'][1]['option_items'][0]['label'])->toBeNull();
});

test('funnel owner cannot save unsupported options detail values', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->from(route('funnels.builder', $funnel))
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Detalhe Invalido',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [
                                [
                                    'id' => 'unsupported-detail',
                                    'type' => 'single_choice',
                                    'label' => '',
                                    'required' => false,
                                    'options_detail' => 'checkbox',
                                    'option_items' => [
                                        [
                                            'id' => 'opt-1',
                                            'label' => 'Opcao 1',
                                            'points' => 1,
                                            'value' => 'A',
                                            'destination' => 'next_stage',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel))
        ->assertSessionHasErrors([
            'stages.0.meta.builder.blocks.0.options_detail',
        ]);
});

test('funnel owner can save a configurable completion page', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil com conclusao',
            'is_active' => false,
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
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => 'Titulo',
                            'subtitle' => '',
                            'button_text' => 'Continuar',
                            'blocks' => [],
                        ],
                    ],
                ],
                [
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => true,
                            'allow_back' => true,
                        ],
                        'builder' => [
                            'title' => 'Fim',
                            'subtitle' => '',
                            'button_text' => 'Enviar',
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    $funnel->refresh();

    expect($funnel->design_settings['completion_page'])->toMatchArray([
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
    ]);
});

test('funnel update preserves disabled charts blocks in stage meta', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();
    $stages[0]->update([
        'meta' => [
            'builder' => [
                'blocks' => [[
                    'id' => 'chart-1',
                    'type' => 'charts',
                    'label' => 'Grafico legado',
                    'required' => false,
                    'legacy_dataset' => ['preservar' => true],
                ]],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Sem Graficos',
            'is_active' => false,
            'stages' => [
                [
                    'id' => $stages[0]->id,
                    'name' => 'Etapa 1',
                    'meta' => [
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
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
                    'id' => $stages[1]->id,
                    'name' => 'Etapa 2',
                    'meta' => [
                        'builder' => [
                            'blocks' => [],
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    $updatedStage = $stages[0]->fresh();

    expect($updatedStage->meta['builder']['blocks'])->toHaveCount(2);
    expect($updatedStage->meta['builder']['blocks'][0]['type'])->toBe('text');
    expect($updatedStage->meta['builder']['blocks'][1])->toMatchArray([
        'id' => 'chart-1',
        'type' => 'charts',
        'legacy_dataset' => ['preservar' => true],
    ]);
});

test('funnel owner can access builder page', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Funil Construtor',
        ]);

    $this->actingAs($user)
        ->get(route('funnels.builder', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Builder')
            ->where('funnel.id', $funnel->id)
            ->where('funnel.name', 'Funil Construtor')
            ->has('funnel.stages', 3)
            ->where('permissions.canEdit', true)
        );
});

test('builder payload strips disabled charts blocks before rendering', function () {
    Log::spy();

    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->create([
            'name' => 'Funil Sem Grafico No Payload',
        ]);

    $funnel->stages()->createMany([
        [
            'name' => 'Etapa 1',
            'stage_order' => 1,
            'meta' => [
                'builder' => [
                    'blocks' => [
                        ['id' => 'chart-legacy', 'type' => 'charts', 'label' => 'Grafico legado', 'required' => false],
                        ['id' => 'text-1', 'type' => 'text', 'label' => 'Nome', 'required' => true],
                    ],
                ],
            ],
        ],
        [
            'name' => 'Etapa 2',
            'stage_order' => 2,
            'meta' => [
                'builder' => [
                    'blocks' => [],
                ],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('funnels.builder', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Builder')
            ->has('funnel.stages.0.meta.builder.blocks', 1)
            ->where('funnel.stages.0.meta.builder.blocks.0.type', 'text')
        );

    expect($funnel->stages()->orderBy('stage_order')->first()->meta['builder']['blocks'])->toHaveCount(2);

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $event, array $context): bool => $event === 'funnel.legacy_blocks_hidden'
            && $context['funnel_id'] === $funnel->id
            && $context['block_type'] === 'charts'
            && $context['block_count'] === 1
    );
});

test('funnel owner can access flow page', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Funil Fluxo',
        ]);

    $this->actingAs($user)
        ->get(route('funnels.flow', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Flow')
            ->where('funnel.id', $funnel->id)
            ->where('permissions.canEdit', true)
        );
});

test('funnel owner can access design page', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Funil Design',
        ]);

    $this->actingAs($user)
        ->get(route('funnels.design', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Design')
            ->where('funnel.id', $funnel->id)
            ->where('designSettings.tokens.colors.primary', '#3d8bff')
            ->where('designSettings.tokens.surfaces.page', '#050d22')
            ->where('designSettings.tokens.typography.family', 'modern')
            ->where('permissions.canEdit', true)
        );
});

test('funnel owner can access leads page', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Funil Leads',
        ]);

    $this->actingAs($user)
        ->get(route('funnels.leads', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Leads')
            ->where('funnel.id', $funnel->id)
            ->where('permissions.canManageLeads', true)
        );
});

test('user cannot access builder page from another owner funnel', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $funnel = Funnel::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('funnels.builder', $funnel))
        ->assertForbidden();
});

test('user cannot access flow page from another owner funnel', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $funnel = Funnel::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('funnels.flow', $funnel))
        ->assertForbidden();
});

test('user cannot access design page from another owner funnel', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $funnel = Funnel::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('funnels.design', $funnel))
        ->assertForbidden();
});

test('user cannot access leads page from another owner funnel', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $funnel = Funnel::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('funnels.leads', $funnel))
        ->assertForbidden();
});

test('shared user can access builder page', function () {
    $owner = User::factory()->create();
    $sharedUser = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $funnel->sharedUsers()->attach($sharedUser->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);

    $this->actingAs($sharedUser)
        ->get(route('funnels.builder', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Builder')
            ->where('funnel.id', $funnel->id)
            ->where('permissions.canEdit', false)
        );
});

test('shared viewer can access flow page in read only mode', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);

    $this->actingAs($viewer)
        ->get(route('funnels.flow', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Flow')
            ->where('funnel.id', $funnel->id)
            ->where('permissions.canEdit', false)
        );
});

test('shared viewer can access design page in read only mode', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);

    $this->actingAs($viewer)
        ->get(route('funnels.design', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Design')
            ->where('funnel.id', $funnel->id)
            ->where('permissions.canEdit', false)
        );
});

test('shared viewer cannot access leads page', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);

    $this->actingAs($viewer)
        ->get(route('funnels.leads', $funnel))
        ->assertForbidden();
});

test('funnel owner can update design settings', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'is_active' => false,
    ]);

    $payload = [
        'is_active' => true,
        'design_settings' => [
            'alignment' => 'left',
            'width' => 'large',
            'elementSize' => 'compact',
            'spacing' => 'large',
            'radius' => 'small',
            'showLogo' => false,
            'showProgress' => false,
            'allowBack' => false,
            'accentColor' => '#112233',
            'pageColor' => '#010203',
            'cardColor' => '#040506',
            'headingColor' => '#aabbcc',
            'textColor' => '#ddeeff',
            'buttonColor' => '#123456',
            'buttonTextColor' => '#ffffff',
            'fontStyle' => 'clean',
            'logoUrl' => 'https://example.com/logo.png',
            'faviconUrl' => 'https://example.com/favicon.png',
            'seoTitle' => 'SEO do funil',
            'seoDescription' => 'Descricao para mecanismos de busca',
            'seoImageUrl' => 'https://example.com/og.png',
            'unavailableTitle' => 'Funil fechado',
            'unavailableDescription' => 'Volte depois.',
            'expiresAt' => '2026-12-31T23:59',
            'tokens' => [
                'colors' => ['textMuted' => '#778899'],
                'surfaces' => ['muted' => '#101820'],
                'borders' => ['default' => '#334455', 'focus' => '#556677'],
                'states' => [
                    'success' => '#118844',
                    'warning' => '#cc8800',
                    'danger' => '#cc3344',
                    'disabledOpacity' => 0.6,
                ],
                'components' => ['fieldBackground' => '#121a24'],
            ],
        ],
        'custom_domain' => 'quiz.exemplo.com',
    ];

    $this->actingAs($owner)
        ->patch(route('funnels.design.update', $funnel), $payload)
        ->assertRedirect(route('funnels.design', $funnel));

    $funnel->refresh();

    expect($funnel->is_active)->toBeTrue();
    $expectedLegacySettings = $payload['design_settings'];
    unset($expectedLegacySettings['tokens']);

    expect($funnel->design_settings)->toMatchArray($expectedLegacySettings);
    expect(data_get($funnel->design_settings, 'tokens.colors.primary'))->toBe('#112233');
    expect(data_get($funnel->design_settings, 'tokens.colors.textMuted'))->toBe('#778899');
    expect(data_get($funnel->design_settings, 'tokens.typography.family'))->toBe('clean');
    expect(data_get($funnel->design_settings, 'tokens.brand.logoUrl'))->toBe('https://example.com/logo.png');
    expect(data_get($funnel->design_settings, 'tokens.brand.showLogo'))->toBeFalse();
    expect(data_get($funnel->design_settings, 'tokens.surfaces.page'))->toBe('#010203');
    expect(data_get($funnel->design_settings, 'tokens.surfaces.card'))->toBe('#040506');
    expect(data_get($funnel->design_settings, 'tokens.surfaces.muted'))->toBe('#101820');
    expect(data_get($funnel->design_settings, 'tokens.components.fieldBackground'))->toBe('#121a24');
    expect(data_get($funnel->design_settings, 'tokens.components.primaryButtonBackground'))->toBe('#123456');
    expect($funnel->custom_domain)->toBe('quiz.exemplo.com');
});

test('funnel owner updating design settings removes orphaned managed design media from r2', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('r2');

    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'is_active' => false,
    ]);

    $logoPath = "funnels/{$funnel->id}/media/image/design-logo.png";
    $faviconPath = "funnels/{$funnel->id}/media/image/design-favicon.png";
    $seoImagePath = "funnels/{$funnel->id}/media/image/design-seo.png";
    $completionImagePath = "funnels/{$funnel->id}/media/image/completion-keep.png";

    Storage::disk('r2')->put($logoPath, 'logo');
    Storage::disk('r2')->put($faviconPath, 'favicon');
    Storage::disk('r2')->put($seoImagePath, 'seo');
    Storage::disk('r2')->put($completionImagePath, 'completion');

    $funnel->update([
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
            'logoUrl' => "https://cdn.example.com/{$logoPath}",
            'faviconUrl' => "https://cdn.example.com/{$faviconPath}",
            'seoTitle' => 'SEO',
            'seoDescription' => 'Descricao SEO',
            'seoImageUrl' => "https://cdn.example.com/{$seoImagePath}",
            'unavailableTitle' => 'Indisponivel',
            'unavailableDescription' => 'Volte depois.',
            'expiresAt' => null,
            'completion_page' => [
                'image_url' => "https://cdn.example.com/{$completionImagePath}",
            ],
        ],
    ]);

    $payload = [
        'is_active' => false,
        'design_settings' => [
            'alignment' => 'left',
            'width' => 'large',
            'elementSize' => 'compact',
            'spacing' => 'large',
            'radius' => 'small',
            'showLogo' => false,
            'showProgress' => false,
            'allowBack' => false,
            'accentColor' => '#112233',
            'pageColor' => '#010203',
            'cardColor' => '#040506',
            'headingColor' => '#aabbcc',
            'textColor' => '#ddeeff',
            'buttonColor' => '#123456',
            'buttonTextColor' => '#ffffff',
            'fontStyle' => 'clean',
            'logoUrl' => '',
            'faviconUrl' => '',
            'seoTitle' => 'SEO novo',
            'seoDescription' => 'Descricao nova',
            'seoImageUrl' => '',
            'unavailableTitle' => 'Fechado',
            'unavailableDescription' => 'Sem acesso.',
            'expiresAt' => null,
        ],
        'custom_domain' => null,
    ];

    $this->actingAs($owner)
        ->patch(route('funnels.design.update', $funnel), $payload)
        ->assertRedirect(route('funnels.design', $funnel));

    Storage::disk('r2')->assertMissing($logoPath);
    Storage::disk('r2')->assertMissing($faviconPath);
    Storage::disk('r2')->assertMissing($seoImagePath);
    Storage::disk('r2')->assertExists($completionImagePath);
});

test('funnel owner updating design settings keeps managed shared design media referenced by another funnel on r2', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('r2');

    $owner = User::factory()->create();
    $sharedLogoPath = 'funnels/shared/media/image/shared-design-logo.png';
    $sharedLogoUrl = "https://cdn.example.com/{$sharedLogoPath}";

    Storage::disk('r2')->put($sharedLogoPath, 'shared-logo');

    $firstFunnel = Funnel::factory()->for($owner)->create([
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
            'logoUrl' => $sharedLogoUrl,
            'faviconUrl' => '',
            'seoTitle' => 'SEO A',
            'seoDescription' => 'Descricao A',
            'seoImageUrl' => '',
            'unavailableTitle' => 'A',
            'unavailableDescription' => 'A',
            'expiresAt' => null,
        ],
    ]);

    $secondFunnel = Funnel::factory()->for($owner)->create([
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
            'logoUrl' => $sharedLogoUrl,
            'faviconUrl' => '',
            'seoTitle' => 'SEO B',
            'seoDescription' => 'Descricao B',
            'seoImageUrl' => '',
            'unavailableTitle' => 'B',
            'unavailableDescription' => 'B',
            'expiresAt' => null,
        ],
    ]);

    $this->actingAs($owner)
        ->patch(route('funnels.design.update', $firstFunnel), [
            'is_active' => false,
            'design_settings' => [
                'alignment' => 'left',
                'width' => 'large',
                'elementSize' => 'compact',
                'spacing' => 'large',
                'radius' => 'small',
                'showLogo' => false,
                'showProgress' => false,
                'allowBack' => false,
                'accentColor' => '#112233',
                'pageColor' => '#010203',
                'cardColor' => '#040506',
                'headingColor' => '#aabbcc',
                'textColor' => '#ddeeff',
                'buttonColor' => '#123456',
                'buttonTextColor' => '#ffffff',
                'fontStyle' => 'clean',
                'logoUrl' => '',
                'faviconUrl' => '',
                'seoTitle' => 'SEO novo',
                'seoDescription' => 'Descricao nova',
                'seoImageUrl' => '',
                'unavailableTitle' => 'Fechado',
                'unavailableDescription' => 'Sem acesso.',
                'expiresAt' => null,
            ],
            'custom_domain' => null,
        ])
        ->assertRedirect(route('funnels.design', $firstFunnel));

    Storage::disk('r2')->assertExists($sharedLogoPath);
    expect($secondFunnel->fresh()->design_settings['logoUrl'])->toBe($sharedLogoUrl);
});

test('shared viewer cannot update design settings', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);

    $this->actingAs($viewer)
        ->patch(route('funnels.design.update', $funnel), [
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
        ])
        ->assertForbidden();
});

test('user cannot update funnel from another owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($otherUser)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($user)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Nao pode',
            'is_active' => true,
            'stages' => [
                ['id' => $stages[0]->id, 'name' => 'x'],
                ['id' => $stages[1]->id, 'name' => 'y'],
            ],
        ])
        ->assertForbidden();
});

test('owner can share funnel by email', function () {
    $owner = User::factory()->create();
    $target = User::factory()->create([
        'email' => 'shared@example.com',
    ]);
    $funnel = Funnel::factory()->for($owner)->create();

    $this->actingAs($owner)
        ->post(route('funnels.share', $funnel), [
            'email' => 'shared@example.com',
            'role' => Funnel::SHARE_ROLE_EDITOR,
        ])
        ->assertRedirect();

    expect($funnel->fresh()->sharedUsers()->whereKey($target->id)->exists())->toBeTrue();
    expect($funnel->fresh()->sharedUsers()->whereKey($target->id)->first()?->pivot?->role)->toBe(Funnel::SHARE_ROLE_EDITOR);
});

test('user without access cannot share funnel', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $target = User::factory()->create([
        'email' => 'target@example.com',
    ]);
    $funnel = Funnel::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->post(route('funnels.share', $funnel), [
            'email' => $target->email,
            'role' => Funnel::SHARE_ROLE_VIEWER,
        ])
        ->assertForbidden();
});

test('shared editor can update funnel from builder', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create([
            'name' => 'Funil Time',
        ]);
    $funnel->sharedUsers()->attach($editor->id, [
        'role' => Funnel::SHARE_ROLE_EDITOR,
    ]);
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($editor)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Funil Time Atualizado',
            'is_active' => true,
            'stages' => [
                ['id' => $stages[0]->id, 'name' => 'Etapa A'],
                ['id' => $stages[1]->id, 'name' => 'Etapa B'],
            ],
        ])
        ->assertRedirect(route('funnels.builder', $funnel));

    expect($funnel->fresh()->name)->toBe('Funil Time Atualizado');
});

test('shared viewer cannot update funnel', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
    ]);
    $stages = $funnel->stages()->orderBy('stage_order')->get();

    $this->actingAs($viewer)
        ->patch(route('funnels.update', $funnel), [
            'name' => 'Nao pode',
            'is_active' => true,
            'stages' => [
                ['id' => $stages[0]->id, 'name' => 'x'],
                ['id' => $stages[1]->id, 'name' => 'y'],
            ],
        ])
        ->assertForbidden();
});
