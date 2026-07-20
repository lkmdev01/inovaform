<?php

namespace App\Support;

use App\Models\Funnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;
use ZipArchive;

class InleadFunnelImporter
{
    private const CACHE_TTL_MINUTES = 15;

    private const MAX_ARCHIVE_ENTRIES = 500;

    private const MAX_ARCHIVE_UNCOMPRESSED_BYTES = 20_000_000;

    private const MAX_REMOTE_IMAGE_BYTES = 8_000_000;

    private const ALLOWED_REMOTE_MEDIA_HOSTS = ['media.inlead.cloud'];

    public function __construct(private ManagedMedia $managedMedia) {}

    /**
     * @return array{token:string,preview:array<string,mixed>}
     */
    public function preview(UploadedFile $file, User $user): array
    {
        $package = $this->readPackage($file);
        $variants = $this->buildVariants($package);
        $defaultLanguage = array_key_first($variants);

        if (! is_string($defaultLanguage)) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo não contém um funil compatível.',
            ]);
        }

        $token = Str::random(64);
        Cache::put($this->cacheKey($token), [
            'user_id' => $user->getKey(),
            'variants' => $variants,
        ], now()->addMinutes(self::CACHE_TTL_MINUTES));

        $blueprint = $variants[$defaultLanguage]['blueprint'];
        $blocks = collect($blueprint['stages'])
            ->flatMap(static fn (array $stage): array => data_get($stage, 'meta.builder.blocks', []))
            ->filter(static fn (mixed $block): bool => is_array($block));
        $componentCounts = $blocks
            ->countBy(static fn (array $block): string => (string) ($block['type'] ?? 'desconhecido'))
            ->sortDesc()
            ->all();
        $remoteMediaUrls = $this->remoteMediaUrls($blueprint);
        $warnings = array_values(array_unique([
            ...$package['warnings'],
            ...($package['type'] === 'inlead' ? [
                'Scripts de rastreamento, cookies, webhooks e domínio personalizado não serão importados.',
                'Revise textos, cálculos e redirecionamentos antes de publicar o funil.',
            ] : []),
        ]));

        return [
            'token' => $token,
            'preview' => [
                'source' => $package['type'] === 'inlead' ? 'Pacote Inlead' : 'Arquivo InovaForm',
                'name' => $blueprint['name'],
                'description' => $blueprint['description'],
                'stage_count' => count($blueprint['stages']),
                'block_count' => $blocks->count(),
                'component_counts' => $componentCounts,
                'image_count' => count($remoteMediaUrls),
                'remote_hosts' => collect($remoteMediaUrls)
                    ->map(static fn (string $url): string => (string) parse_url($url, PHP_URL_HOST))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'languages' => collect($variants)
                    ->map(static fn (array $variant, string $key): array => [
                        'value' => $key,
                        'label' => $variant['label'],
                    ])
                    ->values()
                    ->all(),
                'default_language' => $defaultLanguage,
                'warnings' => $warnings,
                'expires_in_minutes' => self::CACHE_TTL_MINUTES,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function blueprint(string $token, User $user, string $language): array
    {
        $cached = Cache::get($this->cacheKey($token));

        if (! is_array($cached) || (int) ($cached['user_id'] ?? 0) !== (int) $user->getKey()) {
            throw ValidationException::withMessages([
                'token' => 'A pré-visualização expirou. Envie o arquivo novamente.',
            ]);
        }

        $variants = is_array($cached['variants'] ?? null) ? $cached['variants'] : [];
        $variant = is_array($variants[$language] ?? null)
            ? $variants[$language]
            : (is_array(reset($variants)) ? reset($variants) : null);

        if (! is_array($variant) || ! is_array($variant['blueprint'] ?? null)) {
            throw ValidationException::withMessages([
                'language' => 'A versão de idioma selecionada não está disponível.',
            ]);
        }

        return $variant['blueprint'];
    }

    public function forget(string $token): void
    {
        Cache::forget($this->cacheKey($token));
    }

    /**
     * @return array{imported:int,failed:int}
     */
    public function rehostRemoteMedia(Funnel $funnel): array
    {
        $funnel->loadMissing(['stages' => static fn ($query) => $query->orderBy('stage_order')]);
        $remoteUrls = $this->remoteMediaUrls([
            'design_settings' => is_array($funnel->design_settings) ? $funnel->design_settings : [],
            'stages' => $funnel->stages->pluck('meta')->all(),
        ]);
        $resolvedUrls = [];
        $imported = 0;

        foreach ($remoteUrls as $remoteUrl) {
            $localUrl = $this->downloadRemoteImage($remoteUrl, $funnel);

            if ($localUrl === null) {
                continue;
            }

            $resolvedUrls[$remoteUrl] = $localUrl;
            $imported++;
        }

        $failed = count($remoteUrls) - $imported;

        if ($resolvedUrls === []) {
            return compact('imported', 'failed');
        }

        $funnel = Funnel::query()->findOrFail($funnel->getKey());
        $funnel->load(['stages' => static fn ($query) => $query->orderBy('stage_order')]);
        $replace = function (mixed $value) use (&$replace, $resolvedUrls): mixed {
            if (is_array($value)) {
                return collect($value)
                    ->map(fn (mixed $nested): mixed => $replace($nested))
                    ->all();
            }

            if (! is_string($value) || ! array_key_exists($value, $resolvedUrls)) {
                return $value;
            }

            return $resolvedUrls[$value];
        };

        $funnel->forceFill([
            'design_settings' => $replace(is_array($funnel->design_settings) ? $funnel->design_settings : []),
        ])->save();

        foreach ($funnel->stages as $stage) {
            $stage->forceFill([
                'meta' => $replace(is_array($stage->meta) ? $stage->meta : []),
            ])->save();
        }

        return compact('imported', 'failed');
    }

    public function remoteMediaCount(Funnel $funnel): int
    {
        $funnel->loadMissing(['stages' => static fn ($query) => $query->orderBy('stage_order')]);

        return count($this->remoteMediaUrls([
            'design_settings' => is_array($funnel->design_settings) ? $funnel->design_settings : [],
            'stages' => $funnel->stages->pluck('meta')->all(),
        ]));
    }

    /** @param array<string, mixed> $status */
    public function updateRemoteMediaStatus(int $funnelId, array $status): void
    {
        Funnel::query()
            ->whereKey($funnelId)
            ->update(['design_settings->importMedia' => $status]);
    }

    /**
     * @return array{type:string,source:array<string,mixed>,translations:array<string,array<string,mixed>>,warnings:list<string>}
     */
    private function readPackage(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== 'zip') {
            $decoded = $this->decodeJson((string) $file->get());

            if (is_array($decoded['steps'] ?? null) && is_array($decoded['design'] ?? null)) {
                return [
                    'type' => 'inlead',
                    'source' => $decoded,
                    'translations' => [],
                    'warnings' => [],
                ];
            }

            return [
                'type' => 'inovaform',
                'source' => $this->normalizeNativeBlueprint($decoded),
                'translations' => [],
                'warnings' => [],
            ];
        }

        return $this->readZipPackage($file);
    }

    /**
     * @return array{type:string,source:array<string,mixed>,translations:array<string,array<string,mixed>>,warnings:list<string>}
     */
    private function readZipPackage(UploadedFile $file): array
    {
        $archive = new ZipArchive;
        $opened = $archive->open($file->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'file' => 'Não foi possível abrir o pacote ZIP.',
            ]);
        }

        try {
            if ($archive->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw ValidationException::withMessages([
                    'file' => 'O pacote contém arquivos demais para uma importação segura.',
                ]);
            }

            $entries = [];
            $uncompressedBytes = 0;

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);

                if (! is_array($stat)) {
                    continue;
                }

                $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
                $uncompressedBytes += (int) ($stat['size'] ?? 0);
                $entries[basename($name)][] = $index;
            }

            if ($uncompressedBytes > self::MAX_ARCHIVE_UNCOMPRESSED_BYTES) {
                throw ValidationException::withMessages([
                    'file' => 'O pacote descompactado excede o limite de segurança.',
                ]);
            }

            $sourceIndex = $this->singleEntryIndex($entries, 'funnel_decrypted.json');

            if ($sourceIndex === null) {
                throw ValidationException::withMessages([
                    'file' => 'O pacote não contém funnel_decrypted.json.',
                ]);
            }

            $source = $this->decodeJson($this->zipEntryContents($archive, $sourceIndex));
            $translations = [];

            foreach ([
                'english' => 'funnel_translation.json',
                'english_neutral' => 'funnel_translation_neutral.json',
            ] as $key => $filename) {
                $translationIndex = $this->singleEntryIndex($entries, $filename);

                if ($translationIndex !== null) {
                    $translations[$key] = $this->decodeJson($this->zipEntryContents($archive, $translationIndex));
                }
            }

            $sensitiveEntries = collect(['network_log.json', 'key4.db', 'cert9.db', 'cookies.sqlite'])
                ->filter(static fn (string $name): bool => array_key_exists($name, $entries))
                ->values();
            $warnings = $sensitiveEntries->isEmpty()
                ? []
                : ['Arquivos potencialmente sensíveis foram detectados e ignorados: '.$sensitiveEntries->join(', ').'.'];

            return [
                'type' => 'inlead',
                'source' => $source,
                'translations' => $translations,
                'warnings' => $warnings,
            ];
        } finally {
            $archive->close();
        }
    }

    /**
     * @param  array{type:string,source:array<string,mixed>,translations:array<string,array<string,mixed>>,warnings:list<string>}  $package
     * @return array<string,array{label:string,blueprint:array<string,mixed>}>
     */
    private function buildVariants(array $package): array
    {
        if ($package['type'] === 'inovaform') {
            return [
                'original' => [
                    'label' => 'Conteúdo original',
                    'blueprint' => $package['source'],
                ],
            ];
        }

        $original = $this->convertInleadBlueprint($package['source']);
        $variants = [
            'original' => [
                'label' => 'Espanhol original',
                'blueprint' => $original,
            ],
        ];

        foreach ($package['translations'] as $key => $translation) {
            $variants[$key] = [
                'label' => $key === 'english_neutral' ? 'Inglês neutro fornecido' : 'Inglês fornecido',
                'blueprint' => $this->applyTranslation($original, $translation),
            ];
        }

        return $variants;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function convertInleadBlueprint(array $source): array
    {
        $steps = collect($source['steps'] ?? [])
            ->filter(static fn (mixed $step): bool => is_array($step))
            ->values();

        if ($steps->count() < 2) {
            throw ValidationException::withMessages([
                'file' => 'O funil do pacote precisa conter ao menos duas etapas.',
            ]);
        }

        $stageOrderBySourceId = $steps
            ->mapWithKeys(static fn (array $step, int $index): array => [
                (string) ($step['id'] ?? '') => $index + 1,
            ])
            ->filter(static fn (int $order, string $id): bool => $id !== '')
            ->all();
        $design = is_array($source['design'] ?? null) ? $source['design'] : [];
        $seo = is_array($source['seo'] ?? null) ? $source['seo'] : [];
        $title = $this->plainText($source['title'] ?? '') ?: 'Funil importado do Inlead';

        return [
            'name' => Str::limit($title, 120, ''),
            'description' => $this->plainText($source['description'] ?? ''),
            'target_leads' => null,
            'is_active' => false,
            'custom_domain' => null,
            'design_settings' => [
                'colorTheme' => 'custom',
                'accentColor' => $this->color($design['themeColor'] ?? null, '#3d8bff'),
                'pageColor' => $this->color($design['backgroundColor'] ?? null, '#ffffff'),
                'cardColor' => $this->color($design['backgroundColor'] ?? null, '#ffffff'),
                'headingColor' => $this->color($design['titleColor'] ?? null, '#030712'),
                'textColor' => $this->color($design['contentColor'] ?? null, '#000000'),
                'buttonColor' => $this->color($design['themeColor'] ?? null, '#3d8bff'),
                'buttonTextColor' => '#ffffff',
                'fontStyle' => $this->fontStyle($design['featuredFont'] ?? null),
                'logoUrl' => $this->imageUrl($design['logo'] ?? null),
                'faviconUrl' => $this->imageUrl($seo['favicon'] ?? null),
                'seoTitle' => $this->plainText($seo['title'] ?? $title),
                'seoDescription' => $this->plainText($source['description'] ?? ''),
                'showLogo' => $this->imageUrl($design['logo'] ?? null) !== '',
                'showProgress' => true,
                'allowBack' => true,
                'radius' => $this->radius($design['rounded'] ?? null),
                'elementSize' => $this->elementSize($design['elementSize'] ?? null),
            ],
            'stages' => $steps
                ->map(fn (array $step, int $index): array => [
                    'name' => Str::limit($this->plainText($step['title'] ?? '') ?: 'Etapa '.($index + 1), 120, ''),
                    'conversion_rate' => null,
                    'expected_volume' => null,
                    'meta' => [
                        'builder' => [
                            'title' => '',
                            'subtitle' => '',
                            'button_text' => '',
                            'blocks' => collect($step['layers'] ?? [])
                                ->filter(static fn (mixed $layer): bool => is_array($layer))
                                ->map(fn (array $layer): ?array => $this->convertLayer($layer, $stageOrderBySourceId))
                                ->filter()
                                ->values()
                                ->all(),
                        ],
                        'header' => [
                            'show_logo' => true,
                            'show_progress' => (bool) data_get($step, 'options.show_progress', true),
                            'allow_back' => true,
                        ],
                    ],
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $layer
     * @param  array<string, int>  $stageOrderBySourceId
     * @return array<string, mixed>|null
     */
    private function convertLayer(array $layer, array $stageOrderBySourceId): ?array
    {
        $type = (string) ($layer['type'] ?? '');
        $content = is_array($layer['content'] ?? null) ? $layer['content'] : [];
        $id = 'imported-'.Str::lower(Str::random(12));
        $base = [
            'id' => $id,
            'required' => false,
            'label_style' => 'default',
            'text_align' => 'text-left',
            'width_percent' => 100,
            'align_horizontal' => 'start',
            'align_vertical' => 'start',
            'show_after_seconds' => null,
            'display_rule_mode' => 'all',
            'display_rules' => [],
            'display_rule_groups' => [],
        ];

        return match ($type) {
            'text' => [...$base, 'type' => 'content_text', 'label' => '', 'placeholder' => $this->sanitizeHtml($content['text'] ?? '')],
            'image' => [...$base, 'type' => 'image', 'label' => 'Imagem', 'placeholder' => $this->imageUrl($content['image'] ?? null), 'image_ratio' => 'auto', 'image_fit' => 'cover', 'image_radius' => 'medium', 'image_frame' => 'none'],
            'clear' => [...$base, 'type' => 'spacer', 'label' => 'Espaço', 'placeholder' => (string) $this->spacerPixels($content['clear'] ?? null)],
            'field' => $this->convertField($base, $content),
            'options' => $this->convertOptions($base, $content, $stageOrderBySourceId),
            'button' => $this->convertButton($base, $content, $stageOrderBySourceId),
            'alert' => [...$base, 'type' => 'attention', 'label' => '', 'placeholder' => $this->plainTextWithLineBreaks($content['text'] ?? ''), 'attention_style' => $this->attentionStyle($layer), 'attention_emphasis' => true, 'attention_padding' => 'default'],
            'arguments' => [...$base, 'type' => 'arguments', 'label' => 'Argumentos', 'options' => collect($content['arguments'] ?? [])->filter(static fn (mixed $item): bool => is_array($item))->map(fn (array $item): string => $this->plainText($item['text'] ?? ''))->filter()->values()->all()],
            'quotes' => $this->convertTestimonials($base, $content),
            'carousel' => $this->convertCarousel($base, $content),
            'graphics' => $this->convertGraphics($base, $content),
            'metric' => [...$base, 'type' => 'level', 'label' => '', 'placeholder' => '', 'level_title' => $this->plainText($content['title'] ?? ''), 'level_subtitle' => $this->plainText($content['subtitle'] ?? ''), 'level_percentage' => max(0, min(100, (int) ($content['percent'] ?? 0))), 'level_indicator_text' => $this->plainText($content['tooltip'] ?? ''), 'level_legends' => $this->plainText($content['legends'] ?? ''), 'level_show_meter' => true, 'level_show_progress' => true, 'level_type' => 'line', 'level_color' => 'theme'],
            'loading' => [...$base, 'type' => 'loading', 'label' => $this->plainText($content['title'] ?? ''), 'placeholder' => $this->plainTextWithLineBreaks($content['description'] ?? ''), 'loading_start_seconds' => max(0, (int) ($content['starts'] ?? 0)), 'loading_duration_seconds' => max(1, min(120, (int) ($content['seconds'] ?? 5))), 'loading_navigation_action' => 'next_stage', 'loading_target_stage_order' => $this->stageDestination($content['destination'] ?? 'next', $stageOrderBySourceId), 'loading_link' => '', 'loading_show_title' => (bool) ($content['show_title'] ?? true), 'loading_show_progress' => (bool) ($content['show_progress'] ?? true)],
            default => null,
        };
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $content @return array<string, mixed> */
    private function convertField(array $base, array $content): array
    {
        $sourceType = strtolower((string) ($content['type'] ?? 'text'));
        $type = in_array($sourceType, ['email', 'phone', 'number', 'date', 'textarea'], true) ? $sourceType : 'text';

        return [
            ...$base,
            'type' => $type,
            'label' => $this->plainText($content['title'] ?? ''),
            'placeholder' => $this->plainText($content['placeholder'] ?? ''),
            'variable_name' => $this->variableName($content['name'] ?? null),
            'required' => (bool) ($content['required'] ?? false),
            ...($type === 'number' ? ['number_mask' => 'decimal'] : []),
            ...($type === 'phone' ? ['phone_mask' => 'br'] : []),
        ];
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $content @param array<string, int> $stageOrderBySourceId @return array<string, mixed> */
    private function convertOptions(array $base, array $content, array $stageOrderBySourceId): array
    {
        $items = collect($content['options'] ?? [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->values()
            ->map(fn (array $item, int $index): array => [
                'id' => 'option-'.Str::lower(Str::random(12)),
                'label' => $this->plainText($item['label'] ?? ''),
                'points' => $index === 0 ? 1 : 0,
                'value' => Str::upper(chr(65 + ($index % 26))),
                'destination' => $this->stageDestination($item['destination'] ?? 'next', $stageOrderBySourceId),
                'image_url' => $this->imageUrl($item['image'] ?? null),
            ])
            ->all();

        return [
            ...$base,
            'type' => 'single_choice',
            'label' => '',
            'required' => (bool) ($content['required'] ?? true),
            'options' => array_column($items, 'label'),
            'option_items' => $items,
            'options_intro_type' => 'none',
            'options_required_selection' => (bool) ($content['required'] ?? true),
            'options_allow_multiple' => false,
            'options_disable_auto_follow' => false,
            'options_style' => 'simple',
            'options_transparent_image' => true,
            'options_layout' => str_contains((string) ($content['cols'] ?? ''), '2') ? 'grid_2' : 'list',
            'options_orientation' => (string) ($content['orientation'] ?? 'vertical') === 'horizontal' ? 'horizontal' : 'vertical',
            'options_image_ratio' => '1:1',
            'options_disposition' => 'image_text',
            'options_detail' => 'none',
            'options_detail_position' => 'start',
            'options_border_size' => 'small',
            'options_shadow' => 'none',
            'options_spacing' => 'simple',
        ];
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $content @param array<string, int> $stageOrderBySourceId @return array<string, mixed> */
    private function convertButton(array $base, array $content, array $stageOrderBySourceId): array
    {
        $destination = trim((string) ($content['destination'] ?? 'next'));
        $isLink = (string) ($content['type'] ?? 'next') === 'redirect' || filter_var($destination, FILTER_VALIDATE_URL) !== false;

        return [
            ...$base,
            'type' => 'button',
            'label' => $this->plainText($content['label'] ?? 'Continuar'),
            'button_action' => $isLink ? 'open_link' : 'next_stage',
            'button_target_stage_order' => $isLink ? 'next' : $this->stageDestination($destination, $stageOrderBySourceId),
            'button_link' => $isLink && $this->isSafeExternalUrl($destination) ? $destination : '',
            'button_open_new_tab' => (bool) ($content['target'] ?? false),
            'button_color_style' => 'theme',
            'button_animated' => (bool) ($content['pulse'] ?? false),
            'button_elevated' => false,
            'button_sticky_footer' => false,
        ];
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $content @return array<string, mixed> */
    private function convertTestimonials(array $base, array $content): array
    {
        $items = collect($content['quotes'] ?? [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'id' => 'testimonial-'.Str::lower(Str::random(12)),
                'label' => $this->plainText($item['activity'] ?? $item['name'] ?? ''),
                'subtitle' => $this->plainText($item['name'] ?? ''),
                'description' => $this->plainText($item['text'] ?? ''),
                'rating' => max(1, min(5, (int) ($item['rate'] ?? 5))),
                'points' => max(1, min(5, (int) ($item['rate'] ?? 5))),
                'value' => $this->plainText($item['name'] ?? ''),
                'destination' => $this->plainText($item['text'] ?? ''),
                'image_url' => $this->imageUrl($item['image'] ?? null),
            ])
            ->values()
            ->all();

        return [...$base, 'type' => 'testimonials', 'label' => 'Depoimentos', 'options' => [], 'option_items' => $items, 'options_border_size' => 'small', 'options_shadow' => 'none', 'options_spacing' => 'simple', 'testimonials_layout' => (string) ($content['layout'] ?? 'list') === 'carousel' ? 'carousel' : 'list'];
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $content @return array<string, mixed> */
    private function convertCarousel(array $base, array $content): array
    {
        $items = collect($content['items'] ?? [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'id' => 'carousel-'.Str::lower(Str::random(12)),
                'label' => $this->plainText($item['text'] ?? ''),
                'value' => $this->imageUrl($item['image'] ?? null),
                'image_url' => $this->imageUrl($item['image'] ?? null),
                'description' => $this->plainText($item['text'] ?? ''),
                'points' => 0,
                'destination' => $this->plainText($item['text'] ?? ''),
            ])
            ->values()
            ->all();

        return [...$base, 'type' => 'carousel', 'label' => 'Carrossel', 'options' => [], 'option_items' => $items, 'carousel_layout' => in_array((string) ($content['layout'] ?? ''), ['image', 'text', 'image_text'], true) ? (string) $content['layout'] : 'image_text', 'carousel_pagination' => (bool) ($content['pagination'] ?? true), 'carousel_autoplay' => false, 'carousel_autoplay_seconds' => 3, 'carousel_border_type' => 'none'];
    }

    /** @param array<string, mixed> $base @param array<string, mixed> $content @return array<string, mixed> */
    private function convertGraphics(array $base, array $content): array
    {
        $items = collect($content['graphics'] ?? [])
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(function (array $item): array {
                $description = $this->plainText($item['legend'] ?? '');
                $label = Str::before($description, ' ');

                return [
                    'id' => 'metric-'.Str::lower(Str::random(12)),
                    'label' => $label,
                    'value' => max(0, min(100, (int) ($item['percent'] ?? 0))).'%',
                    'description' => $description,
                    'points' => 0,
                    'destination' => $description,
                ];
            })
            ->values()
            ->all();

        return [...$base, 'type' => 'metrics', 'label' => '', 'options' => [], 'option_items' => $items];
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, mixed>  $translation
     * @return array<string, mixed>
     */
    private function applyTranslation(array $blueprint, array $translation): array
    {
        $blueprint['name'] = Str::limit($this->plainText($translation['funnel_title'] ?? $blueprint['name']), 120, '');
        $blueprint['design_settings']['seoTitle'] = $this->plainText($translation['seo_title'] ?? $blueprint['design_settings']['seoTitle']);
        $translatedSteps = collect($translation['steps'] ?? [])->filter(static fn (mixed $step): bool => is_array($step))->values();

        foreach ($blueprint['stages'] as $index => &$stage) {
            $translatedStep = $translatedSteps->get($index);

            if (! is_array($translatedStep)) {
                continue;
            }

            $blocks = &$stage['meta']['builder']['blocks'];
            $contentBlocks = collect($blocks)->keys()->filter(fn (int $key): bool => ($blocks[$key]['type'] ?? null) === 'content_text')->values();
            $headline = $this->translationText($translatedStep['headline'] ?? null);

            if ($headline !== '' && ($contentIndex = $contentBlocks->shift()) !== null) {
                $blocks[$contentIndex]['placeholder'] = $this->paragraphHtml($headline);
            }

            $texts = $this->translationStrings($translatedStep['texts'] ?? []);

            foreach ($texts as $text) {
                $contentIndex = $contentBlocks->shift();

                if ($contentIndex === null) {
                    break;
                }

                $blocks[$contentIndex]['placeholder'] = $this->paragraphHtml($text);
            }

            $optionLabels = $this->translationStrings($translatedStep['options'] ?? []);
            $optionBlockIndex = collect($blocks)->keys()->first(fn (int $key): bool => in_array($blocks[$key]['type'] ?? null, ['options', 'single_choice', 'multiple_choice', 'yes_no'], true));

            if ($optionLabels !== [] && is_int($optionBlockIndex)) {
                foreach ($blocks[$optionBlockIndex]['option_items'] as $optionIndex => &$item) {
                    if (isset($optionLabels[$optionIndex])) {
                        $item['label'] = $optionLabels[$optionIndex];
                    }
                }
                unset($item);
                $blocks[$optionBlockIndex]['options'] = array_column($blocks[$optionBlockIndex]['option_items'], 'label');
            }

            $buttonLabels = $this->translationStrings($translatedStep['button'] ?? []);
            $buttonIndexes = collect($blocks)->keys()->filter(fn (int $key): bool => ($blocks[$key]['type'] ?? null) === 'button')->values();

            foreach ($buttonLabels as $buttonIndex => $buttonLabel) {
                $blockIndex = $buttonIndexes->get($buttonIndex);

                if (is_int($blockIndex)) {
                    $blocks[$blockIndex]['label'] = $buttonLabel;
                }
            }

            $fieldText = $this->translationStrings($translatedStep['field'] ?? []);
            $fieldIndex = collect($blocks)->keys()->first(fn (int $key): bool => in_array($blocks[$key]['type'] ?? null, ['text', 'email', 'phone', 'number', 'textarea', 'date'], true));

            if ($fieldText !== [] && is_int($fieldIndex)) {
                $blocks[$fieldIndex]['placeholder'] = end($fieldText);
            }
        }
        unset($stage);

        return $blueprint;
    }

    /** @return list<string> */
    private function translationStrings(mixed $value): array
    {
        if (is_string($value)) {
            $text = $this->plainText($value);

            return $text !== '' ? [$text] : [];
        }

        if (! is_array($value) && ! is_object($value)) {
            return [];
        }

        return collect((array) $value)
            ->flatMap(fn (mixed $nested): array => $this->translationStrings($nested))
            ->filter()
            ->values()
            ->all();
    }

    private function translationText(mixed $value): string
    {
        return implode(' ', $this->translationStrings($value));
    }

    /** @param array<string, mixed> $decoded @return array<string, mixed> */
    private function normalizeNativeBlueprint(array $decoded): array
    {
        $funnel = is_array($decoded['funnel'] ?? null) ? $decoded['funnel'] : $decoded;
        $stages = collect($funnel['stages'] ?? [])->filter(static fn (mixed $stage): bool => is_array($stage))->values()->all();

        if (count($stages) < 2) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo precisa conter ao menos duas etapas válidas.',
            ]);
        }

        return [
            'name' => trim((string) ($funnel['name'] ?? 'Funil importado')),
            'description' => trim((string) ($funnel['description'] ?? '')),
            'target_leads' => isset($funnel['target_leads']) ? (int) $funnel['target_leads'] : null,
            'is_active' => false,
            'custom_domain' => null,
            'design_settings' => is_array($funnel['design_settings'] ?? null) ? $funnel['design_settings'] : null,
            'stages' => $stages,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => 'Um dos arquivos JSON do pacote está inválido.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'file' => 'O conteúdo JSON do pacote está inválido.',
            ]);
        }

        return $decoded;
    }

    /** @param array<string, list<int>> $entries */
    private function singleEntryIndex(array $entries, string $filename): ?int
    {
        $matches = $entries[$filename] ?? [];

        if (count($matches) > 1) {
            throw ValidationException::withMessages([
                'file' => "O pacote contém múltiplas cópias de {$filename}.",
            ]);
        }

        return $matches[0] ?? null;
    }

    private function zipEntryContents(ZipArchive $archive, int $index): string
    {
        $contents = $archive->getFromIndex($index);

        if (! is_string($contents)) {
            throw ValidationException::withMessages([
                'file' => 'Não foi possível ler os dados do pacote.',
            ]);
        }

        return $contents;
    }

    /** @param array<string, mixed> $blueprint @return list<string> */
    private function remoteMediaUrls(array $blueprint): array
    {
        $urls = [];
        $walk = function (mixed $value) use (&$walk, &$urls): void {
            if (is_array($value)) {
                foreach ($value as $nested) {
                    $walk($nested);
                }

                return;
            }

            if (is_string($value) && $this->isAllowedRemoteMediaUrl($value)) {
                $urls[] = $value;
            }
        };
        $walk($blueprint);

        return array_values(array_unique($urls));
    }

    private function downloadRemoteImage(string $url, Funnel $funnel): ?string
    {
        $disk = $this->managedMedia->imageDisk();
        $pathWithoutExtension = "funnels/{$funnel->id}/media/image/import-".hash('sha256', $url);

        foreach (['jpg', 'png', 'webp', 'gif'] as $existingExtension) {
            $existingPath = "{$pathWithoutExtension}.{$existingExtension}";

            if (Storage::disk($disk)->exists($existingPath)) {
                return $this->managedMedia->publicUrl($disk, $existingPath);
            }
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withoutRedirecting()
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        $body = $response->body();
        $contentType = strtolower(trim(Str::before((string) $response->header('Content-Type'), ';')));

        if (! $response->successful() || ! str_starts_with($contentType, 'image/') || strlen($body) > self::MAX_REMOTE_IMAGE_BYTES) {
            return null;
        }

        $extension = match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };

        if ($extension === null) {
            return null;
        }

        $path = "{$pathWithoutExtension}.{$extension}";

        if (! Storage::disk($disk)->put($path, $body)) {
            return null;
        }

        return $this->managedMedia->publicUrl($disk, $path);
    }

    private function isAllowedRemoteMediaUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $port = parse_url($url, PHP_URL_PORT);
        $user = parse_url($url, PHP_URL_USER);

        return $scheme === 'https'
            && in_array($host, self::ALLOWED_REMOTE_MEDIA_HOSTS, true)
            && $port === null
            && $user === null;
    }

    private function isSafeExternalUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true)
            && parse_url($url, PHP_URL_USER) === null;
    }

    /** @param array<string, mixed> $stageOrderBySourceId */
    private function stageDestination(mixed $destination, array $stageOrderBySourceId): string
    {
        $target = trim((string) $destination);

        if ($target === '' || in_array(strtolower($target), ['next', 'next_stage'], true)) {
            return 'next';
        }

        return isset($stageOrderBySourceId[$target]) ? (string) $stageOrderBySourceId[$target] : 'next';
    }

    private function imageUrl(mixed $image): string
    {
        if (is_string($image)) {
            return $this->isSafeExternalUrl($image) ? $image : '';
        }

        if (! is_array($image)) {
            return '';
        }

        $url = trim((string) ($image['src'] ?? ''));

        return $this->isSafeExternalUrl($url) ? $url : '';
    }

    private function sanitizeHtml(mixed $value): string
    {
        $html = trim((string) $value);

        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = strip_tags($html, '<h1><h2><h3><h4><p><strong><b><em><i><u><ul><ol><li><br>');

        return preg_replace('/\s(?:on\w+|style|class|id|href|src)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    }

    private function plainText(mixed $value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function plainTextWithLineBreaks(mixed $value): string
    {
        $html = (string) $value;
        $html = preg_replace('#<br\s*/?>#i', "\n", $html) ?? '';
        $html = preg_replace('#</(?:p|div|h[1-6]|li)>#i', "\n", $html) ?? '';
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $lines = preg_split('/\R/u', $text) ?: [];
        $lines = array_map(
            static fn (string $line): string => trim(preg_replace('/[\t ]+/u', ' ', $line) ?? ''),
            $lines,
        );

        return implode("\n", array_values(array_filter($lines, static fn (string $line): bool => $line !== '')));
    }

    private function variableName(mixed $value): ?string
    {
        $name = trim((string) $value);

        if ($name === '' || preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name) !== 1) {
            return null;
        }

        return $name;
    }

    private function paragraphHtml(string $text): string
    {
        return '<p>'.htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
    }

    private function color(mixed $value, string $fallback): string
    {
        $color = trim((string) $value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? strtolower($color) : $fallback;
    }

    private function fontStyle(mixed $value): string
    {
        $font = strtolower(trim((string) $value));

        return str_contains($font, 'serif') ? 'serif' : 'modern';
    }

    private function radius(mixed $value): string
    {
        $radius = strtolower((string) $value);

        return str_contains($radius, 'none') ? 'none' : (str_contains($radius, 'full') || str_contains($radius, '3xl') ? 'large' : 'medium');
    }

    private function elementSize(mixed $value): string
    {
        $pixels = (int) filter_var((string) $value, FILTER_SANITIZE_NUMBER_INT);

        return $pixels >= 64 ? 'large' : ($pixels <= 44 && $pixels > 0 ? 'small' : 'default');
    }

    private function spacerPixels(mixed $value): int
    {
        $raw = (string) $value;

        if (preg_match('/([0-9.]+)rem/', $raw, $matches) === 1) {
            return max(8, min(240, (int) round((float) $matches[1] * 16)));
        }

        return 28;
    }

    /** @param array<string, mixed> $layer */
    private function attentionStyle(array $layer): string
    {
        $style = strtolower((string) data_get($layer, 'design.style', 'danger'));

        return match ($style) {
            'warning' => 'amber',
            'info', 'primary' => 'blue',
            default => 'red',
        };
    }

    private function cacheKey(string $token): string
    {
        return 'funnel-import-preview:'.hash('sha256', $token);
    }
}
