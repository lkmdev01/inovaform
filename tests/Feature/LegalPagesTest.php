<?php

use Inertia\Testing\AssertableInertia as Assert;

test('privacy policy page is publicly available', function () {
    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('PrivacyPolicy')
        );
});

test('terms of service page is publicly available', function () {
    $this->get(route('terms-of-service'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TermsOfService')
        );
});
