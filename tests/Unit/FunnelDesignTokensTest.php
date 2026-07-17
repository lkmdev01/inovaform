<?php

use App\Support\FunnelDesignTokens;

test('legacy design settings resolve to semantic funnel tokens', function () {
    $tokens = FunnelDesignTokens::resolve([
        'accentColor' => '#2244AA',
        'pageColor' => '#010203',
        'cardColor' => '#040B22',
        'headingColor' => '#F8FBFF',
        'textColor' => '#9DB7E9',
        'buttonColor' => '#12356F',
        'buttonTextColor' => '#FFFFFF',
        'fontStyle' => 'clean',
        'logoUrl' => ' https://example.com/logo.png ',
        'showLogo' => false,
    ]);

    expect(data_get($tokens, 'colors.primary'))->toBe('#2244aa');
    expect(data_get($tokens, 'surfaces.page'))->toBe('#010203');
    expect(data_get($tokens, 'surfaces.card'))->toBe('#040b22');
    expect(data_get($tokens, 'typography.family'))->toBe('clean');
    expect(data_get($tokens, 'brand.logoUrl'))->toBe('https://example.com/logo.png');
    expect(data_get($tokens, 'brand.showLogo'))->toBeFalse();
    expect(data_get($tokens, 'components.primaryButtonBackground'))->toBe('linear-gradient(135deg, #2563eb, #06b6d4)');
});

test('token overrides are sanitized and invalid imported values fall back safely', function () {
    $tokens = FunnelDesignTokens::resolve([
        'accentColor' => '#3d8bff',
        'tokens' => [
            'colors' => ['primary' => 'url(javascript:alert(1))'],
            'surfaces' => ['muted' => '#ABCDEF'],
            'typography' => ['family' => 'external-font'],
            'states' => ['disabledOpacity' => 8],
        ],
    ]);

    expect(data_get($tokens, 'colors.primary'))->toBe('#3d8bff');
    expect(data_get($tokens, 'surfaces.muted'))->toBe('#abcdef');
    expect(data_get($tokens, 'typography.family'))->toBe('modern');
    expect(data_get($tokens, 'states.disabledOpacity'))->toBe(1.0);
});
