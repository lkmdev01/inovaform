<?php

namespace App\Support;

final class FunnelDesignTokens
{
    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function resolve(array $settings): array
    {
        $overrides = is_array($settings['tokens'] ?? null) ? $settings['tokens'] : [];
        $colors = self::group($overrides, 'colors');
        $typography = self::group($overrides, 'typography');
        $brand = self::group($overrides, 'brand');
        $surfaces = self::group($overrides, 'surfaces');
        $borders = self::group($overrides, 'borders');
        $states = self::group($overrides, 'states');
        $components = self::group($overrides, 'components');

        return [
            'colors' => [
                'primary' => self::color($colors['primary'] ?? null, (string) ($settings['accentColor'] ?? '#3d8bff')),
                'onPrimary' => self::color($colors['onPrimary'] ?? null, (string) ($settings['buttonTextColor'] ?? '#e8f2ff')),
                'heading' => self::color($colors['heading'] ?? null, (string) ($settings['headingColor'] ?? '#f8fbff')),
                'text' => self::color($colors['text'] ?? null, (string) ($settings['textColor'] ?? '#a8bfeb')),
                'textMuted' => self::color($colors['textMuted'] ?? null, '#7894c5'),
            ],
            'typography' => [
                'family' => self::enum($typography['family'] ?? null, ['modern', 'clean', 'serif'], (string) ($settings['fontStyle'] ?? 'modern')),
            ],
            'brand' => [
                'logoUrl' => trim((string) ($brand['logoUrl'] ?? $settings['logoUrl'] ?? '')),
                'showLogo' => (bool) ($brand['showLogo'] ?? $settings['showLogo'] ?? true),
            ],
            'surfaces' => [
                'page' => self::color($surfaces['page'] ?? null, (string) ($settings['pageColor'] ?? '#050d22')),
                'card' => self::color($surfaces['card'] ?? null, (string) ($settings['cardColor'] ?? '#0b1a3a')),
                'muted' => self::color($surfaces['muted'] ?? null, '#102348'),
            ],
            'borders' => [
                'default' => self::color($borders['default'] ?? null, '#2f538f'),
                'strong' => self::color($borders['strong'] ?? null, (string) ($settings['accentColor'] ?? '#3d8bff')),
                'focus' => self::color($borders['focus'] ?? null, (string) ($settings['accentColor'] ?? '#3d8bff')),
            ],
            'states' => [
                'success' => self::color($states['success'] ?? null, '#22c55e'),
                'warning' => self::color($states['warning'] ?? null, '#f59e0b'),
                'danger' => self::color($states['danger'] ?? null, '#f43f5e'),
                'disabledOpacity' => self::opacity($states['disabledOpacity'] ?? null, 0.55),
            ],
            'components' => [
                'fieldBackground' => self::color($components['fieldBackground'] ?? null, '#0b274f'),
                'fieldText' => self::color($components['fieldText'] ?? null, (string) ($settings['headingColor'] ?? '#f8fbff')),
                'primaryButtonBackground' => array_key_exists('primaryButtonBackground', $components)
                    ? self::color($components['primaryButtonBackground'], '#2563eb')
                    : 'linear-gradient(135deg, #2563eb, #06b6d4)',
                'primaryButtonText' => self::color($components['primaryButtonText'] ?? null, (string) ($settings['buttonTextColor'] ?? '#e8f2ff')),
            ],
        ];
    }

    private static function color(mixed $value, string $fallback): string
    {
        $color = trim((string) ($value ?? ''));

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1) {
            return strtolower($color);
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $fallback) === 1 ? strtolower($fallback) : '#000000';
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function enum(mixed $value, array $allowed, string $fallback): string
    {
        $candidate = trim((string) ($value ?? ''));

        if (in_array($candidate, $allowed, true)) {
            return $candidate;
        }

        return in_array($fallback, $allowed, true) ? $fallback : $allowed[0];
    }

    private static function opacity(mixed $value, float $fallback): float
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        return max(0.1, min(1, (float) $value));
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private static function group(array $source, string $key): array
    {
        return is_array($source[$key] ?? null) ? $source[$key] : [];
    }
}
