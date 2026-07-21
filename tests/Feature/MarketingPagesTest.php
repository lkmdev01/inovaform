<?php

test('marketing pages expose InovaForm identity, purpose, and Google data use without login', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('InovaForm')
        ->assertSee('Transforme interesse em')
        ->assertSee('Construtor visual')
        ->assertSee('Do insight')
        ->assertSee('Login com Google disponível com dados mínimos')
        ->assertSee(route('privacy-policy'));

    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertSee('Política de Privacidade — InovaForm')
        ->assertSee('O InovaForm não solicita nem lê e-mails, arquivos, contatos ou calendário da sua conta Google.');
});
