<?php

use App\Support\CustomDomainDiagnostics;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('inovaform.publication.custom_domain_diagnostics_enabled', true);
    config()->set('inovaform.publication.custom_domain_target_host', 'edge.inovaform.example');
    config()->set('inovaform.publication.custom_domain_target_ips', ['198.51.100.20']);
});

test('custom domain diagnostics confirms matching dns and tls', function () {
    $diagnostics = app(CustomDomainDiagnostics::class);
    $dnsResolver = static fn (string $domain): array => [
        'addresses' => ['198.51.100.20'],
        'cnames' => $domain === 'quiz.cliente.example' ? ['edge.inovaform.example'] : [],
    ];

    $result = $diagnostics->diagnose(
        'quiz.cliente.example',
        dnsResolver: $dnsResolver,
        tlsVerifier: static fn (string $domain, array $addresses): bool => $domain === 'quiz.cliente.example' && $addresses === ['198.51.100.20'],
    );

    expect($result)
        ->status->toBe('ready')
        ->dns_ready->toBeTrue()
        ->tls_ready->toBeTrue()
        ->expected_target->toBe('edge.inovaform.example')
        ->checked_at->not->toBeNull();
});

test('custom domain diagnostics reports tls pending after dns is ready', function () {
    $diagnostics = app(CustomDomainDiagnostics::class);

    $result = $diagnostics->diagnose(
        'quiz.cliente.example',
        dnsResolver: static fn (string $domain): array => [
            'addresses' => ['198.51.100.20'],
            'cnames' => $domain === 'quiz.cliente.example' ? ['edge.inovaform.example'] : [],
        ],
        tlsVerifier: static fn (): bool => false,
    );

    expect($result)
        ->status->toBe('tls_pending')
        ->dns_ready->toBeTrue()
        ->tls_ready->toBeFalse();
});

test('custom domain diagnostics reports dns pending for a different destination', function () {
    $diagnostics = app(CustomDomainDiagnostics::class);

    $result = $diagnostics->diagnose(
        'quiz.cliente.example',
        dnsResolver: static fn (string $domain): array => [
            'addresses' => [$domain === 'edge.inovaform.example' ? '198.51.100.20' : '203.0.113.90'],
            'cnames' => [],
        ],
        tlsVerifier: static fn (): bool => true,
    );

    expect($result)
        ->status->toBe('pending_dns')
        ->dns_ready->toBeFalse()
        ->tls_ready->toBeFalse();
});
