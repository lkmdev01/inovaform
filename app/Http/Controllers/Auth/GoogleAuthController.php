<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()
                ->route('login')
                ->with('status', 'O login com Google ainda não foi configurado pelo administrador.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()
                ->route('login')
                ->with('status', 'O login com Google ainda não foi configurado pelo administrador.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->with('status', 'Não foi possível concluir o login com Google. Tente novamente.');
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));
        $googleId = trim((string) $googleUser->getId());

        if ($email === '' || $googleId === '') {
            return redirect()
                ->route('login')
                ->with('status', 'A conta Google não forneceu um e-mail válido.');
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $user instanceof User) {
            $user = User::query()->create([
                'name' => trim((string) $googleUser->getName()) ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
