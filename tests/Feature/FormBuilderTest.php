<?php

use App\Models\User;

test('guests are redirected when accessing form builder routes', function () {
    $this->get(route('forms.builder'))->assertRedirect(route('login'));

    $this->post(route('forms.store'), [
        'name' => 'Landing Form',
        'status' => 'draft',
        'fields' => [
            [
                'label' => 'Nome',
                'type' => 'text',
                'is_required' => true,
                'options' => [],
            ],
        ],
    ])->assertRedirect(route('login'));
});

test('authenticated users are redirected from legacy form builder to funnels index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('forms.builder'))
        ->assertRedirect(route('funnels.index'));
});

test('authenticated users are redirected from legacy form template store to funnels index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('forms.store'), [])
        ->assertRedirect(route('funnels.index'));
});
