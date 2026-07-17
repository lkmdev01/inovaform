<?php

namespace App\Console\Commands;

use App\Models\Funnel;
use App\Models\FunnelTemplate;
use App\Support\ManagedMedia;
use App\Support\OperationalTelemetry;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class CleanupFunnelMediaCommand extends Command
{
    protected $signature = 'app:cleanup-funnel-media
        {--dry-run : Apenas listar arquivos orfaos sem remover}
        {--older-than-days= : Remove apenas arquivos mais antigos que a politica de retencao}';

    protected $description = 'Remove arquivos de midia orfaos dos funis.';

    public function __construct(private ManagedMedia $managedMedia)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $retentionDays = max(0, (int) ($this->option('older-than-days') ?? config('inovaform.media.cleanup_retention_days', 14)));
        $retentionCutoff = now()->subDays($retentionDays)->getTimestamp();

        $existingFiles = collect($this->managedMedia->managedDisks())
            ->flatMap(function (string $disk): array {
                return collect(Storage::disk($disk)->allFiles('funnels'))
                    ->filter(static fn (string $path): bool => str_contains($path, '/media/'))
                    ->map(fn (string $path): string => $this->managedMedia->referenceKey($disk, $path))
                    ->values()
                    ->all();
            })
            ->values();

        $referencedFiles = Funnel::query()
            ->with('stages:id,funnel_id,meta')
            ->get()
            ->flatMap(function (Funnel $funnel): array {
                $designSettings = is_array($funnel->design_settings) ? $funnel->design_settings : [];
                $designPaths = $this->resolveDesignSettingsPaths($designSettings);

                $stagePaths = $funnel->stages
                    ->flatMap(function ($stage): array {
                        $meta = is_array($stage->meta) ? $stage->meta : [];
                        $blocks = data_get($meta, 'builder.blocks', []);

                        if (! is_array($blocks)) {
                            return [];
                        }

                        return collect($blocks)
                            ->filter(static fn ($block): bool => is_array($block))
                            ->flatMap(function (array $block): array {
                                $paths = [
                                    $this->normalizeStoragePath($block['placeholder'] ?? null),
                                    $this->normalizeStoragePath($block['audio_src'] ?? null),
                                    $this->normalizeStoragePath($block['audio_avatar_url'] ?? null),
                                    $this->normalizeStoragePath($block['notification_avatar_url'] ?? null),
                                ];

                                $optionItemPaths = collect($block['option_items'] ?? [])
                                    ->filter(static fn ($item): bool => is_array($item))
                                    ->flatMap(fn (array $item): array => [
                                        $this->normalizeStoragePath($item['value'] ?? null),
                                        $this->normalizeStoragePath($item['image_url'] ?? null),
                                    ])
                                    ->filter()
                                    ->values()
                                    ->all();

                                return [...$paths, ...$optionItemPaths];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    })
                    ->values()
                    ->all();

                return [...$designPaths, ...$stagePaths];
            })
            ->merge(
                FunnelTemplate::query()
                    ->pluck('thumbnail_path')
                    ->map(fn (mixed $path): ?string => $this->normalizeStoragePath($path))
                    ->filter()
                    ->values()
            )
            ->filter()
            ->unique()
            ->values();

        $orphanedFiles = $existingFiles
            ->filter(function (string $reference) use ($retentionCutoff, $retentionDays): bool {
                [$disk, $path] = explode('::', $reference, 2);
                $filesystem = Storage::disk($disk);

                if ($retentionDays <= 0) {
                    return true;
                }

                return $filesystem->lastModified($path) <= $retentionCutoff;
            })
            ->reject(static fn (string $reference): bool => $referencedFiles->contains($reference))
            ->values();

        if ($orphanedFiles->isEmpty()) {
            $this->info('Nenhum arquivo orfao encontrado.');
            OperationalTelemetry::info('funnel_media.cleanup', [
                'orphaned_files' => 0,
                'dry_run' => (bool) $this->option('dry-run'),
                'retention_days' => $retentionDays,
            ]);

            return self::SUCCESS;
        }

        $this->info('Arquivos orfaos encontrados: ' . $orphanedFiles->count());

        foreach ($orphanedFiles as $reference) {
            [, $path] = explode('::', $reference, 2);
            $this->line($path);
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run ativo. Nenhum arquivo foi removido.');
            OperationalTelemetry::info('funnel_media.cleanup', [
                'orphaned_files' => $orphanedFiles->count(),
                'dry_run' => true,
                'retention_days' => $retentionDays,
            ]);

            if ($orphanedFiles->count() >= (int) config('inovaform.media.orphan_alert_threshold', 25)) {
                OperationalTelemetry::warning('funnel_media.cleanup_threshold_reached', [
                    'orphaned_files' => $orphanedFiles->count(),
                    'retention_days' => $retentionDays,
                ]);
            }

            return self::SUCCESS;
        }

        $orphanedFiles
            ->map(static fn (string $reference): array => array_combine(['disk', 'path'], explode('::', $reference, 2)))
            ->filter(static fn ($entry): bool => is_array($entry))
            ->groupBy('disk')
            ->each(function ($entries, string $disk): void {
                $paths = collect($entries)->pluck('path')->all();
                $filesystem = Storage::disk($disk);

                $filesystem->delete($paths);

                if ($disk === 'public') {
                    $this->deleteEmptyMediaDirectories($filesystem, $paths);
                }
            });

        $this->info("Arquivos removidos: {$orphanedFiles->count()}");
        OperationalTelemetry::warning('funnel_media.cleanup', [
            'orphaned_files' => $orphanedFiles->count(),
            'dry_run' => false,
            'retention_days' => $retentionDays,
            'removed_paths' => $orphanedFiles
                ->map(static fn (string $reference): string => explode('::', $reference, 2)[1] ?? $reference)
                ->values()
                ->all(),
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteEmptyMediaDirectories(Filesystem $disk, array $paths): void
    {
        collect($paths)
            ->map(static fn (string $path): string => trim(str_replace('\\', '/', dirname($path)), '/'))
            ->filter(static fn (string $directory): bool => $directory !== '.' && $directory !== '')
            ->unique()
            ->sortDesc()
            ->each(function (string $directory) use ($disk): void {
                if ($disk->exists($directory) && count($disk->allFiles($directory)) === 0) {
                    $disk->deleteDirectory($directory);
                }
            });
    }

    private function normalizeStoragePath(mixed $value): ?string
    {
        $reference = $this->managedMedia->parseReference($value);

        return $reference !== null
            ? $this->managedMedia->referenceKey($reference['disk'], $reference['path'])
            : null;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<string>
     */
    private function resolveDesignSettingsPaths(array $settings): array
    {
        $completionPage = is_array($settings['completion_page'] ?? null) ? $settings['completion_page'] : [];

        return collect([
            $this->normalizeStoragePath($settings['logoUrl'] ?? null),
            $this->normalizeStoragePath($settings['faviconUrl'] ?? null),
            $this->normalizeStoragePath($settings['seoImageUrl'] ?? null),
            $this->normalizeStoragePath($completionPage['image_url'] ?? null),
        ])
            ->filter()
            ->values()
            ->all();
    }
}
