<?php

test('marketing pages expose InovaForm identity, purpose, and Google data use without login', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('InovaForm: crie funis, formulários e automações em um só lugar.')
        ->assertSee('O InovaForm ajuda empresas e criadores a criar, publicar e otimizar páginas, formulários e funis de vendas.')
        ->assertSee('Ao entrar com Google, o InovaForm usa somente nome, e-mail e foto de perfil')
        ->assertSee(route('privacy-policy'));

    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertSee('Política de Privacidade — InovaForm')
        ->assertSee('O InovaForm não solicita nem lê e-mails, arquivos, contatos ou calendário da sua conta Google.');
});
