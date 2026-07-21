<?php

test('returns a public marketing homepage', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('Transforme interesse em')
        ->assertSee('Política de Privacidade');
});
