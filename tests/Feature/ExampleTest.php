<?php

test('returns a public marketing homepage', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('InovaForm: crie funis, formulários e automações em um só lugar.')
        ->assertSee('Política de Privacidade');
});
