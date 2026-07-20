<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Validator;

test('application uses Brazilian Portuguese as its default locale', function () {
    expect(config('app.locale'))->toBe('pt_BR')
        ->and(config('app.fallback_locale'))->toBe('pt_BR');
});

test('validation messages and attribute names are translated', function () {
    $errors = Validator::make(
        ['email' => 'formato-invalido', 'password' => 'segredo'],
        ['email' => ['required', 'email'], 'password' => ['confirmed']],
    )->errors();

    expect($errors->first('email'))
        ->toBe('O campo e-mail deve conter um endereço de e-mail válido.')
        ->and($errors->first('password'))
        ->toBe('A confirmação do campo senha não corresponde.');
});

test('password reset notification is translated', function () {
    $message = (new ResetPassword('token'))->toMail(User::factory()->make());

    expect($message->subject)
        ->toBe('Redefinição de senha')
        ->and($message->actionText)
        ->toBe('Redefinir senha')
        ->and($message->introLines)
        ->toContain('Você está recebendo este e-mail porque recebemos uma solicitação para redefinir a senha da sua conta.');
});
