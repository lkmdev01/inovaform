<?php

use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\FunnelSubmission;
use App\Models\FunnelSubmissionAnswer;
use App\Models\FunnelTemplate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    config()->set('services.groq.api_key', 'configured-test-key');
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $systemTemplate = FunnelTemplate::factory()->create([
        'name' => 'Captura de Leads',
        'slug' => 'captura-de-leads',
        'is_system' => true,
        'is_premium' => true,
        'sort_order' => 1,
        'version' => 3,
        'category' => 'captacao',
        'thumbnail_path' => 'https://example.com/system.png',
    ]);
    $customTemplate = FunnelTemplate::factory()->ownedBy($user)->create([
        'name' => 'Template do Time',
        'slug' => 'template-do-time',
        'sort_order' => 2,
        'version' => 1,
        'category' => 'evento',
    ]);
    Funnel::factory()
        ->for($user)
        ->has(FunnelStage::factory()->count(3), 'stages')
        ->create([
            'name' => 'Dashboard Funil',
            'target_leads' => 1200,
        ]);
    $sharedFunnel = Funnel::factory()
        ->for($owner)
        ->has(FunnelStage::factory()->count(2), 'stages')
        ->create([
            'name' => 'Funil Compartilhado',
        ]);
    $sharedFunnel->sharedUsers()->attach($user->id);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('funnels', 1)
            ->where('funnels.0.name', 'Dashboard Funil')
            ->has('sharedFunnels', 1)
            ->where('sharedFunnels.0.name', 'Funil Compartilhado')
            ->has('templates', 2)
            ->where('templates.0.id', $systemTemplate->id)
            ->where('templates.0.name', 'Captura de Leads')
            ->where('templates.0.is_premium', true)
            ->where('templates.0.version', 3)
            ->where('templates.0.thumbnail_path', 'https://example.com/system.png')
            ->where('templates.1.id', $customTemplate->id)
            ->where('templates.1.name', 'Template do Time')
            ->where('templates.1.is_system', false)
            ->has('templateCategories', 2)
            ->where('aiGenerationEnabled', true)
            ->has('stats')
            ->where('stats.currentFunnels', 1)
        );
});

test('dashboard uses real submission metrics for leads and conversion', function () {
    $user = User::factory()->create();
    $funnel = Funnel::factory()
        ->for($user)
        ->create([
            'name' => 'Funil Real',
            'target_leads' => 1200,
        ]);

    $stageOne = FunnelStage::factory()->for($funnel)->create([
        'stage_order' => 1,
        'name' => 'Etapa 1',
    ]);
    $stageTwo = FunnelStage::factory()->for($funnel)->create([
        'stage_order' => 2,
        'name' => 'Etapa 2',
    ]);

    $submissionOnlyStart = FunnelSubmission::factory()->for($funnel)->create();
    $submissionCompleted = FunnelSubmission::factory()->for($funnel)->create();

    FunnelSubmissionAnswer::factory()->for($submissionOnlyStart, 'submission')->create([
        'funnel_stage_id' => $stageOne->id,
        'block_label' => 'Inicio',
    ]);

    FunnelSubmissionAnswer::factory()->for($submissionCompleted, 'submission')->create([
        'funnel_stage_id' => $stageOne->id,
        'block_label' => 'Inicio',
    ]);
    FunnelSubmissionAnswer::factory()->for($submissionCompleted, 'submission')->create([
        'funnel_stage_id' => $stageTwo->id,
        'block_label' => 'Fim',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.registeredLeads', 2)
            ->where('funnels.0.dashboard_metrics.leads', 2)
            ->where('funnels.0.dashboard_metrics.started', 2)
            ->where('funnels.0.dashboard_metrics.completed', 1)
            ->where('funnels.0.dashboard_metrics.conversion', 50)
        );
});
