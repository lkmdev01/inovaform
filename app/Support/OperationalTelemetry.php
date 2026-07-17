<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

final class OperationalTelemetry
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        Log::info($event, self::normalizeContext('info', $event, $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        Log::warning($event, self::normalizeContext('warning', $event, $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $event, array $context = []): void
    {
        Log::error($event, self::normalizeContext('error', $event, $context));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function normalizeContext(string $severity, string $event, array $context): array
    {
        return array_merge([
            'severity' => $severity,
            'event' => $event,
            'captured_at' => now()->toISOString(),
            'app' => config('app.name'),
            'environment' => config('app.env'),
        ], $context);
    }
}
