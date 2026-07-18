<?php

use App\Models\Funnel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('inovaform.publication.custom_domain_diagnostics_enabled', false);
});

test('funnel owner can open the funnel settings page', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'custom_domain' => 'quiz.example.com',
        'design_settings' => [
            'seoTitle' => 'SEO atual',
            'completion_page' => ['enabled' => true],
        ],
    ]);

    $this->actingAs($owner)
        ->get(route('funnels.settings', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Settings')
            ->where('funnel.id', $funnel->id)
            ->where('funnel.custom_domain', 'quiz.example.com')
            ->where('settings.seoTitle', 'SEO atual')
            ->where('permissions.canEdit', true)
        );
});

test('shared viewer can read funnel settings but cannot update them', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create();
    $funnel->sharedUsers()->attach($viewer->id, [
        'role' => Funnel::SHARE_ROLE_VIEWER,
        'shared_by_user_id' => $owner->id,
    ]);

    $this->actingAs($viewer)
        ->get(route('funnels.settings', $funnel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('funnels/Settings')
            ->where('permissions.canEdit', false)
        );

    $this->actingAs($viewer)
        ->patch(route('funnels.settings.update', $funnel), [
            'is_active' => false,
        ])
        ->assertForbidden();
});

test('funnel settings update publication seo and domain without replacing design data', function () {
    $owner = User::factory()->create();
    $funnel = Funnel::factory()->for($owner)->create([
        'is_active' => false,
        'design_settings' => [
            'alignment' => 'center',
            'tokens' => [
                'brand' => ['logoUrl' => 'https://old.example/logo.png', 'showLogo' => true],
                'colors' => ['primary' => '#123456'],
            ],
            'completion_page' => ['enabled' => true, 'title' => 'Obrigado'],
        ],
    ]);

    $response = $this->actingAs($owner)
        ->patch(route('funnels.settings.update', $funnel), [
            'is_active' => true,
            'custom_domain' => 'QUIZ.NOVO.COM',
            'logo_url' => 'https://cdn.example.com/logo.png',
            'favicon_url' => 'https://cdn.example.com/favicon.png',
            'seo_title' => 'Novo título SEO',
            'seo_description' => 'Nova descrição SEO',
            'seo_image_url' => 'https://cdn.example.com/seo.png',
            'expires_at' => '2030-06-15T15:00:00.000Z',
            'unavailable_title' => 'Indisponível',
            'unavailable_description' => 'Volte mais tarde.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('funnels.settings', $funnel));

    $funnel->refresh();

    expect($funnel->is_active)->toBeTrue()
        ->and($funnel->custom_domain)->toBe('quiz.novo.com')
        ->and($funnel->design_settings['seoTitle'])->toBe('Novo título SEO')
        ->and($funnel->design_settings['seoDescription'])->toBe('Nova descrição SEO')
        ->and($funnel->design_settings['logoUrl'])->toBe('https://cdn.example.com/logo.png')
        ->and($funnel->design_settings['tokens']['brand']['logoUrl'])->toBe('https://cdn.example.com/logo.png')
        ->and($funnel->design_settings['tokens']['colors']['primary'])->toBe('#123456')
        ->and($funnel->design_settings['completion_page']['title'])->toBe('Obrigado');
});

test('funnel settings reject invalid and duplicated custom domains', function () {
    $owner = User::factory()->create();
    $firstFunnel = Funnel::factory()->for($owner)->create([
        'custom_domain' => 'usado.example.com',
    ]);
    $secondFunnel = Funnel::factory()->for($owner)->create();

    $this->actingAs($owner)
        ->from(route('funnels.settings', $secondFunnel))
        ->patch(route('funnels.settings.update', $secondFunnel), [
            'is_active' => true,
            'custom_domain' => 'https://invalido.example.com/path',
        ])
        ->assertSessionHasErrors('custom_domain');

    $this->actingAs($owner)
        ->from(route('funnels.settings', $secondFunnel))
        ->patch(route('funnels.settings.update', $secondFunnel), [
            'is_active' => true,
            'custom_domain' => $firstFunnel->custom_domain,
        ])
        ->assertSessionHasErrors('custom_domain');
});
