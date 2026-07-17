<?php

use Illuminate\Support\Facades\Storage;

test('monitor funnel health counts media files from the configured media disk', function () {
    config()->set('inovaform.media.disk', 'r2');

    Storage::fake('public');
    Storage::fake('r2');

    Storage::disk('public')->put('funnels/1/media/image/public-only.png', 'public');
    Storage::disk('r2')->put('funnels/1/media/image/r2-image.png', 'image');
    Storage::disk('r2')->put('funnels/1/media/audio/r2-audio.mp3', 'audio');

    $this->artisan('app:monitor-funnel-health')
        ->expectsTable(
            ['Funis', 'Ativos', 'Leads 24h', 'Leads parados 7d', 'Arquivos de midia'],
            [[0, 0, 0, 0, 2]]
        )
        ->assertSuccessful();
});
