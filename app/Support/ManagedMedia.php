<?php

namespace App\Support;

use App\Models\Funnel;
use App\Models\FunnelTemplate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Storage;

class ManagedMedia
{
    public function mediaDisk(): string
    {
        return (string) config('inovaform.media.disk', 'public');
    }

    public function imageDisk(): string
    {
        return $this->mediaDisk();
    }

    /**
     * @return list<string>
     */
    public function managedDisks(): array
    {
        return array_values(array_unique([
            'public',
            $this->mediaDisk(),
        ]));
    }

    public function diskForKind(string $kind): string
    {
        return in_array($kind, ['image', 'audio'], true) ? $this->mediaDisk() : 'public';
    }

    public function publicUrl(string $disk, string $path): string
    {
        $normalizedPath = ltrim($path, '/');

        if ($disk === 'public') {
            return '/media/' . $normalizedPath;
        }

        $baseUrl = $this->diskBaseUrl($disk);

        if ($baseUrl !== null) {
            return $baseUrl . '/' . $normalizedPath;
        }

        return Storage::disk($disk)->url($normalizedPath);
    }

    public function referenceKey(string $disk, string $path): string
    {
        return "{$disk}::" . ltrim($path, '/');
    }

    /**
     * @return list<string>
     */
    public function referencedKeysForFunnel(Funnel $funnel): array
    {
        $funnel->loadMissing([
            'stages' => static fn ($query) => $query->select(['id', 'funnel_id', 'meta'])->orderBy('stage_order'),
        ]);

        $designSettings = is_array($funnel->design_settings) ? $funnel->design_settings : [];
        $stageKeys = $this->referencedKeysForStages($funnel->stages);

        return collect([
            ...$this->referencedKeysForDesignSettings($designSettings),
            ...$stageKeys,
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function globallyReferencedKeys(): array
    {
        $funnelKeys = Funnel::query()
            ->with([
                'stages' => static fn ($query) => $query->select(['id', 'funnel_id', 'meta'])->orderBy('stage_order'),
            ])
            ->get(['id', 'design_settings'])
            ->flatMap(fn (Funnel $funnel): array => $this->referencedKeysForFunnel($funnel))
            ->values()
            ->all();

        $templateKeys = FunnelTemplate::query()
            ->pluck('thumbnail_path')
            ->map(fn (mixed $value): ?string => $this->normalizeReferenceKey($value))
            ->filter()
            ->values()
            ->all();

        return collect([
            ...$funnelKeys,
            ...$templateKeys,
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $referenceKeys
     * @return list<string>
     */
    public function pruneUnusedReferenceKeys(array $referenceKeys): array
    {
        $candidateKeys = collect($referenceKeys)
            ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn (string $value): string => trim($value))
            ->unique()
            ->values();

        if ($candidateKeys->isEmpty()) {
            return [];
        }

        $activeKeys = collect($this->globallyReferencedKeys());
        $orphanedKeys = $candidateKeys
            ->reject(static fn (string $referenceKey): bool => $activeKeys->contains($referenceKey))
            ->values();

        if ($orphanedKeys->isEmpty()) {
            return [];
        }

        $this->deleteReferenceKeys($orphanedKeys->all());

        return $orphanedKeys->all();
    }

    /**
     * @param  list<string>  $referenceKeys
     */
    public function deleteReferenceKeys(array $referenceKeys): void
    {
        collect($referenceKeys)
            ->map(function (string $referenceKey): ?array {
                $parts = explode('::', $referenceKey, 2);

                if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
                    return null;
                }

                return [
                    'disk' => $parts[0],
                    'path' => $parts[1],
                ];
            })
            ->filter()
            ->groupBy('disk')
            ->each(function ($entries, string $disk): void {
                $paths = collect($entries)
                    ->pluck('path')
                    ->filter(static fn (mixed $path): bool => is_string($path) && trim($path) !== '')
                    ->values()
                    ->all();

                if ($paths === []) {
                    return;
                }

                $filesystem = Storage::disk($disk);
                $filesystem->delete($paths);

                if ($disk === 'public') {
                    $this->deleteEmptyDirectories($filesystem, $paths);
                }
            });
    }

    /**
     * @return array{disk:string,path:string}|null
     */
    public function parseReference(mixed $value): ?array
    {
        $path = trim((string) ($value ?? ''));

        if ($path === '') {
            return null;
        }

        $localPath = $this->normalizeLocalPath($path);

        if ($localPath !== null) {
            return [
                'disk' => 'public',
                'path' => $localPath,
            ];
        }

        if (preg_match('#^https?://#i', $path) !== 1) {
            return str_contains($path, 'funnels/')
                ? ['disk' => 'public', 'path' => ltrim($path, '/')]
                : null;
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);

        if (! is_string($parsedPath) || trim($parsedPath) === '') {
            return null;
        }

        $normalizedParsedPath = $this->normalizeLocalPath($parsedPath);

        if ($normalizedParsedPath !== null) {
            return [
                'disk' => 'public',
                'path' => $normalizedParsedPath,
            ];
        }

        foreach ($this->managedDisks() as $disk) {
            if ($disk === 'public') {
                continue;
            }

            $baseUrl = $this->diskBaseUrl($disk);

            if ($baseUrl === null) {
                continue;
            }

            if ($path === $baseUrl) {
                return null;
            }

            if (str_starts_with($path, $baseUrl . '/')) {
                return [
                    'disk' => $disk,
                    'path' => ltrim(substr($path, strlen($baseUrl) + 1), '/'),
                ];
            }
        }

        return null;
    }

    private function normalizeLocalPath(string $path): ?string
    {
        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        if (str_starts_with($path, 'storage/')) {
            return ltrim(substr($path, strlen('storage/')), '/');
        }

        if (str_starts_with($path, '/media/')) {
            return ltrim(substr($path, strlen('/media/')), '/');
        }

        if (str_starts_with($path, 'media/')) {
            return ltrim(substr($path, strlen('media/')), '/');
        }

        return null;
    }

    private function diskBaseUrl(string $disk): ?string
    {
        $value = trim((string) config("filesystems.disks.{$disk}.url", ''));

        return $value !== '' ? rtrim($value, '/') : null;
    }

    /**
     * @param  EloquentCollection<int, \App\Models\FunnelStage>  $stages
     * @return list<string>
     */
    private function referencedKeysForStages(EloquentCollection $stages): array
    {
        return $stages
            ->flatMap(function ($stage): array {
                $meta = is_array($stage->meta) ? $stage->meta : [];
                $blocks = data_get($meta, 'builder.blocks', []);

                if (! is_array($blocks)) {
                    return [];
                }

                return collect($blocks)
                    ->filter(static fn ($block): bool => is_array($block))
                    ->flatMap(function (array $block): array {
                        $blockKeys = [
                            $this->normalizeReferenceKey($block['placeholder'] ?? null),
                            $this->normalizeReferenceKey($block['audio_src'] ?? null),
                            $this->normalizeReferenceKey($block['audio_avatar_url'] ?? null),
                            $this->normalizeReferenceKey($block['notification_avatar_url'] ?? null),
                        ];

                        $optionItemKeys = collect($block['option_items'] ?? [])
                            ->filter(static fn ($item): bool => is_array($item))
                            ->flatMap(fn (array $item): array => [
                                $this->normalizeReferenceKey($item['value'] ?? null),
                                $this->normalizeReferenceKey($item['image_url'] ?? null),
                            ])
                            ->filter()
                            ->values()
                            ->all();

                        return [...$blockKeys, ...$optionItemKeys];
                    })
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<string>
     */
    private function referencedKeysForDesignSettings(array $settings): array
    {
        $completionPage = is_array($settings['completion_page'] ?? null) ? $settings['completion_page'] : [];

        return collect([
            $this->normalizeReferenceKey($settings['logoUrl'] ?? null),
            $this->normalizeReferenceKey($settings['faviconUrl'] ?? null),
            $this->normalizeReferenceKey($settings['seoImageUrl'] ?? null),
            $this->normalizeReferenceKey($completionPage['image_url'] ?? null),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeReferenceKey(mixed $value): ?string
    {
        $reference = $this->parseReference($value);

        return $reference !== null
            ? $this->referenceKey($reference['disk'], $reference['path'])
            : null;
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteEmptyDirectories(Filesystem $filesystem, array $paths): void
    {
        collect($paths)
            ->map(static fn (string $path): string => trim(str_replace('\\', '/', dirname($path)), '/'))
            ->filter(static fn (string $directory): bool => $directory !== '.' && $directory !== '')
            ->unique()
            ->sortDesc()
            ->each(function (string $directory) use ($filesystem): void {
                if ($filesystem->exists($directory) && count($filesystem->allFiles($directory)) === 0) {
                    $filesystem->deleteDirectory($directory);
                }
            });
    }
}
