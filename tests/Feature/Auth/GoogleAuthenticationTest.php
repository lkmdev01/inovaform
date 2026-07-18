<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function configureGoogleAuthentication(): void
{
    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.client_secret', 'google-client-secret');
    config()->set('services.google.redirect', '/auth/google/callback');
}

test('google login redirects back to login when credentials are not configured', function () {
    config()->set('services.google.client_id');
    config()->set('services.google.client_secret');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');
});

test('user can start google authentication', function () {
    configureGoogleAuthentication();
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))->assertRedirect();
});

test('login and registration screens expose google login when configured', function () {
    configureGoogleAuthentication();

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('googleAuthEnabled', true)
        );

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Register')
            ->where('googleAuthEnabled', true)
        );
});

test('new user can authenticate with google', function () {
    configureGoogleAuthentication();
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'Pessoa Google',
        'email' => 'pessoa@example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'Pessoa Google',
        'email' => 'pessoa@example.com',
        'google_id' => 'google-123',
        'avatar_url' => 'https://example.com/avatar.png',
    ]);

    expect(User::query()->where('email', 'pessoa@example.com')->first()?->email_verified_at)->not->toBeNull();
});

test('google authentication links an existing account by email', function () {
    configureGoogleAuthentication();
    $existingUser = User::factory()->create([
        'email' => 'existente@example.com',
        'google_id' => null,
    ]);
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-existing',
        'name' => 'Nome Google',
        'email' => 'EXISTENTE@example.com',
        'avatar' => 'https://example.com/new-avatar.png',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($existingUser);
    expect(User::query()->count())->toBe(1)
        ->and($existingUser->fresh()->google_id)->toBe('google-existing')
        ->and($existingUser->fresh()->avatar_url)->toBe('https://example.com/new-avatar.png');
});
