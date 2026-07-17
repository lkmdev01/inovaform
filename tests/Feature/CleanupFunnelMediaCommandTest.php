<?php

use App\Models\Funnel;
use App\Models\FunnelTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('inovaform.media.disk', 'public');
});

test('cleanup funnel media command removes orphaned files and keeps referenced assets', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create();

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
                        'id' => 'image_block',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => '/storage/funnels/1/media/keep-image.png',
                        'required' => false,
                    ],
                    [
                        'id' => 'audio_block',
                        'type' => 'audio',
                        'label' => '',
                        'audio_src' => '/storage/funnels/1/media/keep-audio.mp3',
                        'audio_avatar_url' => '/storage/funnels/1/media/keep-avatar.png',
                        'required' => false,
                    ],
                    [
                        'id' => 'options_block',
                        'type' => 'single_choice',
                        'label' => '',
                        'option_items' => [
                            [
                                'id' => 'option-1',
                                'label' => 'Com imagem',
                                'image_url' => '/storage/funnels/1/media/keep-option.png',
                            ],
                        ],
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    Storage::disk('public')->put('funnels/1/media/keep-image.png', 'image');
    Storage::disk('public')->put('funnels/1/media/keep-audio.mp3', 'audio');
    Storage::disk('public')->put('funnels/1/media/keep-avatar.png', 'avatar');
    Storage::disk('public')->put('funnels/1/media/keep-option.png', 'option');
    Storage::disk('public')->put('funnels/1/media/orphan-file.png', 'orphan');

    $this->artisan('app:cleanup-funnel-media --older-than-days=0')
        ->expectsOutputToContain('Arquivos orfaos encontrados: 1')
        ->expectsOutputToContain('funnels/1/media/orphan-file.png')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('funnels/1/media/keep-image.png');
    Storage::disk('public')->assertExists('funnels/1/media/keep-audio.mp3');
    Storage::disk('public')->assertExists('funnels/1/media/keep-avatar.png');
    Storage::disk('public')->assertExists('funnels/1/media/keep-option.png');
    Storage::disk('public')->assertMissing('funnels/1/media/orphan-file.png');
});

test('cleanup funnel media command keeps notification and design assets referenced by the funnel', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $funnelId = 1;
    $funnel = Funnel::factory()->for($owner)->create([
        'design_settings' => [
            'logoUrl' => "/media/funnels/{$funnelId}/media/image/keep-logo.png",
            'faviconUrl' => "/media/funnels/{$funnelId}/media/image/keep-favicon.png",
            'seoImageUrl' => "/media/funnels/{$funnelId}/media/image/keep-seo.png",
            'completion_page' => [
                'image_url' => "/media/funnels/{$funnelId}/media/image/keep-completion.png",
            ],
        ],
    ]);

    $funnelId = $funnel->id;
    $funnel->update([
        'design_settings' => [
            'logoUrl' => "/media/funnels/{$funnelId}/media/image/keep-logo.png",
            'faviconUrl' => "/media/funnels/{$funnelId}/media/image/keep-favicon.png",
            'seoImageUrl' => "/media/funnels/{$funnelId}/media/image/keep-seo.png",
            'completion_page' => [
                'image_url' => "/media/funnels/{$funnelId}/media/image/keep-completion.png",
            ],
        ],
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
                        'id' => 'notification_block',
                        'type' => 'notification',
                        'label' => '',
                        'notification_avatar_url' => "/media/funnels/{$funnelId}/media/image/keep-notification-avatar.png",
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    Storage::disk('public')->put("funnels/{$funnelId}/media/image/keep-logo.png", 'logo');
    Storage::disk('public')->put("funnels/{$funnelId}/media/image/keep-favicon.png", 'favicon');
    Storage::disk('public')->put("funnels/{$funnelId}/media/image/keep-seo.png", 'seo');
    Storage::disk('public')->put("funnels/{$funnelId}/media/image/keep-completion.png", 'completion');
    Storage::disk('public')->put("funnels/{$funnelId}/media/image/keep-notification-avatar.png", 'notification-avatar');
    Storage::disk('public')->put("funnels/{$funnelId}/media/image/orphan-design.png", 'orphan-design');

    $this->artisan('app:cleanup-funnel-media --older-than-days=0')
        ->expectsOutputToContain('Arquivos orfaos encontrados: 1')
        ->expectsOutputToContain("funnels/{$funnelId}/media/image/orphan-design.png")
        ->assertSuccessful();

    Storage::disk('public')->assertExists("funnels/{$funnelId}/media/image/keep-logo.png");
    Storage::disk('public')->assertExists("funnels/{$funnelId}/media/image/keep-favicon.png");
    Storage::disk('public')->assertExists("funnels/{$funnelId}/media/image/keep-seo.png");
    Storage::disk('public')->assertExists("funnels/{$funnelId}/media/image/keep-completion.png");
    Storage::disk('public')->assertExists("funnels/{$funnelId}/media/image/keep-notification-avatar.png");
    Storage::disk('public')->assertMissing("funnels/{$funnelId}/media/image/orphan-design.png");
});

test('cleanup funnel media command dry run does not remove orphaned files', function () {
    Storage::fake('public');

    Storage::disk('public')->put('funnels/99/media/orphan-file.png', 'orphan');

    $this->artisan('app:cleanup-funnel-media --dry-run --older-than-days=0')
        ->expectsOutputToContain('funnels/99/media/orphan-file.png')
        ->expectsOutputToContain('Dry-run ativo. Nenhum arquivo foi removido.')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('funnels/99/media/orphan-file.png');
});

test('cleanup funnel media command respects retention window before removing orphaned files', function () {
    Storage::fake('public');

    Storage::disk('public')->put('funnels/55/media/recent-orphan.png', 'recent');
    Storage::disk('public')->put('funnels/55/media/old-orphan.png', 'old');

    $recentPath = Storage::disk('public')->path('funnels/55/media/recent-orphan.png');
    $oldPath = Storage::disk('public')->path('funnels/55/media/old-orphan.png');

    touch($recentPath, now()->subDays(2)->getTimestamp());
    touch($oldPath, now()->subDays(30)->getTimestamp());

    $this->artisan('app:cleanup-funnel-media --older-than-days=14')
        ->expectsOutputToContain('Arquivos orfaos encontrados: 1')
        ->expectsOutputToContain('funnels/55/media/old-orphan.png')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('funnels/55/media/recent-orphan.png');
    Storage::disk('public')->assertMissing('funnels/55/media/old-orphan.png');
});

test('cleanup funnel media command removes orphaned files from configured r2 disk and keeps referenced assets', function () {
    config()->set('inovaform.media.disk', 'r2');
    config()->set('filesystems.disks.r2.url', 'https://cdn.example.com');

    Storage::fake('public');
    Storage::fake('r2');

    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'design_settings' => [
            'logoUrl' => 'https://cdn.example.com/funnels/88/media/image/keep-logo.png',
            'completion_page' => [
                'image_url' => 'https://cdn.example.com/funnels/88/media/image/keep-completion.png',
            ],
        ],
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
                        'id' => 'image_block',
                        'type' => 'image',
                        'label' => '',
                        'placeholder' => 'https://cdn.example.com/funnels/88/media/image/keep-stage.png',
                        'required' => false,
                    ],
                    [
                        'id' => 'notification_block',
                        'type' => 'notification',
                        'label' => '',
                        'notification_avatar_url' => 'https://cdn.example.com/funnels/88/media/image/keep-notification.png',
                        'required' => false,
                    ],
                    [
                        'id' => 'audio_block',
                        'type' => 'audio',
                        'label' => '',
                        'audio_src' => 'https://cdn.example.com/funnels/88/media/audio/keep-audio.mp3',
                        'audio_avatar_url' => 'https://cdn.example.com/funnels/88/media/audio/keep-avatar.png',
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    FunnelTemplate::query()->create([
        'user_id' => $owner->id,
        'source_funnel_id' => $funnel->id,
        'name' => 'Template R2',
        'slug' => 'template-r2',
        'description' => null,
        'category' => null,
        'thumbnail_path' => 'https://cdn.example.com/funnels/88/media/image/keep-thumbnail.png',
        'is_system' => false,
        'is_premium' => false,
        'is_active' => true,
        'sort_order' => 0,
        'version' => 1,
        'schema' => [],
    ]);

    Storage::disk('r2')->put('funnels/88/media/image/keep-logo.png', 'logo');
    Storage::disk('r2')->put('funnels/88/media/image/keep-completion.png', 'completion');
    Storage::disk('r2')->put('funnels/88/media/image/keep-stage.png', 'stage');
    Storage::disk('r2')->put('funnels/88/media/image/keep-notification.png', 'notification');
    Storage::disk('r2')->put('funnels/88/media/image/keep-thumbnail.png', 'thumbnail');
    Storage::disk('r2')->put('funnels/88/media/image/orphan-r2.png', 'orphan');
    Storage::disk('r2')->put('funnels/88/media/audio/keep-audio.mp3', 'audio');
    Storage::disk('r2')->put('funnels/88/media/audio/keep-avatar.png', 'avatar');

    $this->artisan('app:cleanup-funnel-media --older-than-days=0')
        ->expectsOutputToContain('Arquivos orfaos encontrados: 1')
        ->expectsOutputToContain('funnels/88/media/image/orphan-r2.png')
        ->assertSuccessful();

    Storage::disk('r2')->assertExists('funnels/88/media/image/keep-logo.png');
    Storage::disk('r2')->assertExists('funnels/88/media/image/keep-completion.png');
    Storage::disk('r2')->assertExists('funnels/88/media/image/keep-stage.png');
    Storage::disk('r2')->assertExists('funnels/88/media/image/keep-notification.png');
    Storage::disk('r2')->assertExists('funnels/88/media/image/keep-thumbnail.png');
    Storage::disk('r2')->assertExists('funnels/88/media/audio/keep-audio.mp3');
    Storage::disk('r2')->assertExists('funnels/88/media/audio/keep-avatar.png');
    Storage::disk('r2')->assertMissing('funnels/88/media/image/orphan-r2.png');
});
