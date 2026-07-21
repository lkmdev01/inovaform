<?php

use Inertia\Testing\AssertableInertia as Assert;

test('privacy policy page is publicly available', function () {
    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertSee('Política de Privacidade — InovaForm')
        ->assertSee('O InovaForm não solicita nem lê e-mails, arquivos, contatos ou calendário da sua conta Google.');
});

test('terms of service page is publicly available', function () {
    $this->get(route('terms-of-service'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TermsOfService')
        );
});
