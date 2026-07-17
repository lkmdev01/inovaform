<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportFunnelRequest;
use App\Http\Requests\ShareFunnelRequest;
use App\Http\Requests\StoreFunnelRequest;
use App\Http\Requests\StoreFunnelTemplateRequest;
use App\Http\Requests\UploadFunnelMediaRequest;
use App\Http\Requests\UpdateFunnelDesignRequest;
use App\Http\Requests\UpdateFunnelRequest;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\FunnelSubmission;
use App\Models\FunnelTemplate;
use App\Models\User;
use App\Support\ManagedMedia;
use App\Support\FunnelDesignTokens;
use App\Support\OperationalTelemetry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class FunnelController extends Controller
{
    public function __construct(private ManagedMedia $managedMedia)
    {
    }

    public function index(Request $request): Response
    {
        $funnels = Funnel::query()
            ->whereBelongsTo($request->user())
            ->with([
                'stages' => static fn ($query) => $query->orderBy('stage_order'),
            ])
            ->latest()
            ->get();

        return Inertia::render('funnels/Index', [
            'funnels' => $funnels,
        ]);
    }

    public function store(StoreFunnelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $template = isset($validated['template_id'])
            ? FunnelTemplate::query()
                ->whereKey((int) $validated['template_id'])
                ->where('is_active', true)
                ->where(static function ($query) use ($request): void {
                    $query
                        ->where('is_system', true)
                        ->orWhere('user_id', $request->user()?->id);
                })
                ->first()
            : null;

        $templateSchema = is_array($template?->schema) ? $template->schema : [];
        $blueprint = [
            'name' => $validated['name'],
            'description' => ($validated['description'] ?? null) ?: ($template?->description ?? null),
            'target_leads' => $validated['target_leads'] ?? ($templateSchema['target_leads'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'custom_domain' => null,
            'design_settings' => is_array($templateSchema['design_settings'] ?? null) ? $templateSchema['design_settings'] : null,
            'stages' => $template !== null ? (array) ($templateSchema['stages'] ?? []) : (array) ($validated['stages'] ?? []),
        ];

        $funnel = DB::transaction(function () use ($request, $blueprint): Funnel {
            return $this->createFunnelFromBlueprint($request->user(), $blueprint);
        });

        return redirect()
            ->route('funnels.builder', $funnel)
            ->with('status', 'funnel-created');
    }

    public function duplicate(Request $request, Funnel $funnel): RedirectResponse
    {
        abort_unless($funnel->canEdit($request->user()), 403);

        $duplicate = DB::transaction(function () use ($request, $funnel): Funnel {
            return $this->createFunnelFromBlueprint($request->user(), $this->serializeFunnelForTransfer($funnel), [
                'name' => $this->duplicateFunnelName($funnel->name),
                'is_active' => false,
                'custom_domain' => null,
            ]);
        });

        return redirect()
            ->route('funnels.builder', $duplicate)
            ->with('status', 'funnel-duplicated');
    }

    public function export(Request $request, Funnel $funnel): StreamedResponse
    {
        abort_unless($funnel->canView($request->user()), 403);

        $payload = [
            'schema_version' => 1,
            'exported_at' => now()->toISOString(),
            'funnel' => $this->serializeFunnelForTransfer($funnel),
        ];

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, Str::slug($funnel->name) . '.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function import(ImportFunnelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $raw = $request->file('file')?->get();

        if (!is_string($raw) || trim($raw) === '') {
            throw ValidationException::withMessages([
                'file' => 'Nao foi possivel ler o arquivo JSON enviado.',
            ]);
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo JSON esta invalido.',
            ]);
        }

        $blueprint = $this->normalizeImportedFunnelBlueprint($decoded);
        $nameOverride = trim((string) ($validated['name'] ?? ''));

        $funnel = DB::transaction(function () use ($request, $blueprint, $nameOverride): Funnel {
            return $this->createFunnelFromBlueprint($request->user(), $blueprint, [
                'name' => $nameOverride !== '' ? $nameOverride : ($blueprint['name'] ?? 'Funil importado'),
                'is_active' => false,
                'custom_domain' => null,
            ]);
        });

        return redirect()
            ->route('funnels.builder', $funnel)
            ->with('status', 'funnel-imported');
    }

    public function storeTemplate(StoreFunnelTemplateRequest $request, Funnel $funnel): RedirectResponse
    {
        abort_unless($funnel->canEdit($request->user()), 403);

        $validated = $request->validated();
        $name = trim((string) $validated['name']);
        $category = trim((string) ($validated['category'] ?? ''));
        $description = trim((string) ($validated['description'] ?? ''));
        $thumbnailPath = trim((string) ($validated['thumbnail_path'] ?? ''));
        $version = (int) FunnelTemplate::query()
            ->where('user_id', $request->user()?->id)
            ->where('source_funnel_id', $funnel->id)
            ->max('version');

        $schema = $this->buildTemplateSchemaFromFunnel($funnel);

        FunnelTemplate::query()->create([
            'user_id' => $request->user()?->id,
            'source_funnel_id' => $funnel->id,
            'name' => $name,
            'slug' => $this->generateUniqueTemplateSlug($name),
            'description' => $description !== '' ? $description : ($funnel->description ?? null),
            'category' => $category !== '' ? $category : null,
            'thumbnail_path' => $thumbnailPath !== '' ? $thumbnailPath : null,
            'is_system' => false,
            'is_premium' => (bool) ($validated['is_premium'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => 0,
            'version' => $version + 1,
            'schema' => $schema,
        ]);

        return back()->with('status', 'template-created');
    }

    public function destroy(Request $request, Funnel $funnel): RedirectResponse
    {
        abort_unless($funnel->isOwnedBy($request->user()), 403);

        $previousReferenceKeys = $this->managedMedia->referencedKeysForFunnel($funnel);
        $funnel->delete();
        $this->pruneRemovedManagedMedia($funnel->id, $previousReferenceKeys, 'destroy');

        return redirect()
            ->route('dashboard')
            ->with('status', 'funnel-deleted');
    }

    public function builder(Request $request, Funnel $funnel): Response
    {
        $canAccess = $funnel->canView($request->user());
        $canEdit = $funnel->canEdit($request->user());

        abort_unless($canAccess, 403);

        $funnel->load([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);
        $this->sanitizeLoadedFunnelStages($funnel);

        return Inertia::render('funnels/Builder', [
            'funnel' => $funnel,
            'permissions' => [
                'canEdit' => $canEdit,
                'canShare' => $funnel->canShare($request->user()),
                'canManageLeads' => $funnel->canManageLeads($request->user()),
                'role' => $funnel->isOwnedBy($request->user()) ? 'owner' : ($funnel->sharedRoleFor($request->user()) ?? 'viewer'),
            ],
        ]);
    }

    public function flow(Request $request, Funnel $funnel): Response
    {
        abort_unless($funnel->canView($request->user()), 403);

        $funnel->load([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);
        $this->sanitizeLoadedFunnelStages($funnel);

        return Inertia::render('funnels/Flow', [
            'funnel' => $funnel,
            'permissions' => [
                'canEdit' => $funnel->canEdit($request->user()),
                'canShare' => $funnel->canShare($request->user()),
                'canManageLeads' => $funnel->canManageLeads($request->user()),
                'role' => $funnel->isOwnedBy($request->user()) ? 'owner' : ($funnel->sharedRoleFor($request->user()) ?? 'viewer'),
            ],
        ]);
    }

    public function design(Request $request, Funnel $funnel): Response
    {
        abort_unless($funnel->canView($request->user()), 403);

        $funnel->load([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);
        $this->sanitizeLoadedFunnelStages($funnel);

        return Inertia::render('funnels/Design', [
            'funnel' => $funnel,
            'designSettings' => $this->resolveDesignSettings($funnel->design_settings),
            'permissions' => [
                'canEdit' => $funnel->canEdit($request->user()),
                'canShare' => $funnel->canShare($request->user()),
                'canManageLeads' => $funnel->canManageLeads($request->user()),
                'role' => $funnel->isOwnedBy($request->user()) ? 'owner' : ($funnel->sharedRoleFor($request->user()) ?? 'viewer'),
            ],
        ]);
    }

    public function updateDesign(UpdateFunnelDesignRequest $request, Funnel $funnel): RedirectResponse
    {
        abort_unless($funnel->canEdit($request->user()), 403);

        $previousReferenceKeys = $this->managedMedia->referencedKeysForFunnel($funnel);
        $validated = $request->validated();
        $existingSettings = is_array($funnel->design_settings) ? $funnel->design_settings : [];
        $submittedSettings = $validated['design_settings'];
        $submittedTokens = is_array($submittedSettings['tokens'] ?? null) ? $submittedSettings['tokens'] : [];
        $submittedComponents = is_array($submittedTokens['components'] ?? null) ? $submittedTokens['components'] : [];

        if (!array_key_exists('primaryButtonBackground', $submittedComponents)) {
            $submittedComponents['primaryButtonBackground'] = $submittedSettings['buttonColor'];
            $submittedTokens['components'] = $submittedComponents;
            $submittedSettings['tokens'] = $submittedTokens;
        }

        $designSettings = $this->sanitizeDesignSettings($submittedSettings, $existingSettings);

        if (array_key_exists('completion_page', $existingSettings)) {
            $designSettings['completion_page'] = $existingSettings['completion_page'];
        }

        $funnel->update([
            'design_settings' => $designSettings,
            'custom_domain' => $this->sanitizeCustomDomain($validated['custom_domain'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? $funnel->is_active),
        ]);
        $funnel->refresh();
        $this->pruneRemovedManagedMedia($funnel->id, $previousReferenceKeys, 'design_update', $funnel);

        return redirect()
            ->route('funnels.design', $funnel)
            ->with('status', isset($validated['is_active']) && (bool) $validated['is_active'] ? 'funnel-published' : 'design-saved');
    }

    public function leads(Request $request, Funnel $funnel): Response
    {
        abort_unless($funnel->canManageLeads($request->user()), 403);

        $funnel->load([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);
        $this->sanitizeLoadedFunnelStages($funnel);

        $search = trim((string) $request->string('q'));
        $status = trim((string) $request->string('status'));
        $tag = trim((string) $request->string('tag'));
        $assigneeId = trim((string) $request->string('assignee_id'));
        $priority = trim((string) $request->string('priority'));
        $hasNotes = trim((string) $request->string('has_notes'));
        $period = trim((string) $request->string('period', '30d'));

        $fromDate = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            default => now()->subDays(30),
        };

        $baseQuery = FunnelSubmission::query()
            ->where('funnel_id', $funnel->id)
            ->where('submitted_at', '>=', $fromDate);

        if ($search !== '') {
            $baseQuery->where(static function ($builder) use ($search): void {
                $builder
                    ->where('lead_name', 'like', "%{$search}%")
                    ->orWhere('lead_email', 'like', "%{$search}%")
                    ->orWhere('lead_phone', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $baseQuery->where('status', $status);
        }

        if ($tag !== '') {
            $baseQuery->whereJsonContains('meta->tags', $tag);
        }

        if ($assigneeId !== '' && ctype_digit($assigneeId)) {
            $baseQuery->where('meta->assignee_id', (int) $assigneeId);
        }

        if ($priority !== '') {
            $baseQuery->where('meta->priority', $priority);
        }

        if ($hasNotes === 'yes') {
            $baseQuery->whereNotNull('meta->notes')->where('meta->notes', '!=', '');
        }

        $leads = (clone $baseQuery)
            ->with([
                'answers' => static function ($query): void {
                    $query->with('stage:id,name')->orderBy('funnel_stage_id')->orderBy('id');
                },
            ])
            ->latest('submitted_at')
            ->paginate(18)
            ->withQueryString()
            ->through(function (FunnelSubmission $submission): array {
                $meta = is_array($submission->meta) ? $submission->meta : [];
                $answersByStage = $submission->answers
                    ->groupBy('funnel_stage_id')
                    ->map(static function ($answers): string {
                        return $answers
                            ->take(2)
                            ->map(static function ($answer): string {
                                $values = collect($answer->value ?? [])->filter(static fn ($value): bool => (string) $value !== '');

                                if ($values->isEmpty()) {
                                    return (string) $answer->block_label;
                                }

                                return (string) $answer->block_label . ': ' . $values->implode(', ');
                            })
                            ->implode(' | ');
                    });

                return [
                    'id' => $submission->id,
                    'status' => $submission->status,
                    'lead_name' => $submission->lead_name,
                    'lead_email' => $submission->lead_email,
                    'lead_phone' => $submission->lead_phone,
                    'submitted_at' => optional($submission->submitted_at)?->toISOString(),
                    'tags' => array_values(array_filter(
                        array_map(static fn (mixed $value): string => trim((string) $value), (array) ($meta['tags'] ?? [])),
                        static fn (string $value): bool => $value !== ''
                    )),
                    'notes' => trim((string) ($meta['notes'] ?? '')),
                    'last_contacted_at' => isset($meta['last_contacted_at']) ? (string) $meta['last_contacted_at'] : null,
                    'next_follow_up_at' => isset($meta['next_follow_up_at']) ? (string) $meta['next_follow_up_at'] : null,
                    'priority' => trim((string) ($meta['priority'] ?? 'normal')) ?: 'normal',
                    'assignee' => [
                        'id' => isset($meta['assignee_id']) ? (int) $meta['assignee_id'] : null,
                        'name' => trim((string) ($meta['assignee_name'] ?? '')),
                    ],
                    'stage_values' => $answersByStage,
                    'answers' => $submission->answers
                        ->map(static function ($answer): array {
                            return [
                                'id' => $answer->id,
                                'stage_name' => (string) ($answer->stage?->name ?? ''),
                                'block_label' => trim((string) $answer->block_label),
                                'value' => collect($answer->value ?? [])
                                    ->map(static fn (mixed $value): string => trim((string) $value))
                                    ->filter(static fn (string $value): bool => $value !== '')
                                    ->implode(', '),
                            ];
                        })
                        ->filter(static fn (array $answer): bool => $answer['block_label'] !== '' || $answer['value'] !== '')
                        ->values()
                        ->all(),
                    'timeline' => collect($meta['timeline'] ?? [])
                        ->filter(static fn ($event): bool => is_array($event))
                        ->map(static function (array $event): array {
                            return [
                                'id' => (string) ($event['id'] ?? ''),
                                'type' => (string) ($event['type'] ?? 'update'),
                                'source' => (string) ($event['source'] ?? 'panel'),
                                'actor_name' => trim((string) ($event['actor_name'] ?? 'Sistema')),
                                'title' => trim((string) ($event['title'] ?? '')),
                                'description' => trim((string) ($event['description'] ?? '')),
                                'metadata' => is_array($event['metadata'] ?? null) ? $event['metadata'] : [],
                                'created_at' => isset($event['created_at']) ? (string) $event['created_at'] : null,
                            ];
                        })
                        ->filter(static fn (array $event): bool => $event['title'] !== '')
                        ->values()
                        ->all(),
                ];
            });

        $totalLeads = (clone $baseQuery)->count();
        $qualifiedLeads = (clone $baseQuery)->where('status', 'qualified')->count();
        $firstStage = $funnel->stages->first();
        $lastStage = $funnel->stages->last();

        $leadsWithInteraction = $firstStage
            ? (clone $baseQuery)->whereHas('answers', static fn ($query) => $query->where('funnel_stage_id', $firstStage->id))->count()
            : 0;

        $completedFlows = $lastStage
            ? (clone $baseQuery)->whereHas('answers', static fn ($query) => $query->where('funnel_stage_id', $lastStage->id))->count()
            : 0;

        $interactionRate = $totalLeads > 0
            ? round(($leadsWithInteraction / $totalLeads) * 100, 1)
            : 0.0;

        $previousCount = max($totalLeads, 1);
        $stageStats = $funnel->stages->map(function (FunnelStage $stage) use ($baseQuery, &$previousCount): array {
            $stageCount = (clone $baseQuery)
                ->whereHas('answers', static fn ($query) => $query->where('funnel_stage_id', $stage->id))
                ->count();

            $conversion = $previousCount > 0
                ? round(($stageCount / $previousCount) * 100, 1)
                : 0.0;

            $previousCount = max($stageCount, 1);

            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'count' => $stageCount,
                'conversion' => $conversion,
            ];
        })->values();

        return Inertia::render('funnels/Leads', [
            'funnel' => [
                'id' => $funnel->id,
                'name' => $funnel->name,
                'slug' => $funnel->slug,
                'is_active' => $funnel->is_active,
            ],
            'designSettings' => $this->resolveDesignSettings($funnel->design_settings),
            'permissions' => [
                'canEdit' => $funnel->canEdit($request->user()),
                'canShare' => $funnel->canShare($request->user()),
                'canManageLeads' => $funnel->canManageLeads($request->user()),
                'role' => $funnel->isOwnedBy($request->user()) ? 'owner' : ($funnel->sharedRoleFor($request->user()) ?? 'viewer'),
            ],
            'metrics' => [
                'visits_and_access' => $totalLeads,
                'leads_acquired' => $totalLeads,
                'interaction_rate' => $interactionRate,
                'qualified_leads' => $qualifiedLeads,
                'completed_flows' => $completedFlows,
            ],
            'stageStats' => $stageStats,
            'leads' => $leads,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'tag' => $tag,
                'assignee_id' => $assigneeId,
                'priority' => $priority,
                'has_notes' => $hasNotes,
                'period' => in_array($period, ['24h', '7d', '30d'], true) ? $period : '30d',
            ],
            'assigneeOptions' => $this->assignableUsersForFunnel($funnel)
                ->map(static fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                ])
                ->values()
                ->all(),
            'statusOptions' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => 'new', 'label' => 'Novo'],
                ['value' => 'contacted', 'label' => 'Contatado'],
                ['value' => 'qualified', 'label' => 'Qualificado'],
                ['value' => 'lost', 'label' => 'Perdido'],
            ],
            'priorityOptions' => [
                ['value' => '', 'label' => 'Todas'],
                ['value' => 'low', 'label' => 'Baixa'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'Alta'],
                ['value' => 'urgent', 'label' => 'Urgente'],
            ],
        ]);
    }

    public function update(UpdateFunnelRequest $request, Funnel $funnel): RedirectResponse
    {
        abort_unless($funnel->canEdit($request->user()), 403);

        $previousReferenceKeys = $this->managedMedia->referencedKeysForFunnel($funnel);
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($funnel, $validated): void {
                $designSettings = is_array($funnel->design_settings) ? $funnel->design_settings : [];

                if (array_key_exists('completion_page', $validated)) {
                    $designSettings['completion_page'] = $this->sanitizeCompletionPage($validated['completion_page'] ?? null);
                }

                $funnel->update([
                    'name' => $validated['name'],
                    'slug' => $this->generateUniqueSlug($validated['name'], $funnel->id),
                    'is_active' => $validated['is_active'],
                    'design_settings' => $designSettings,
                ]);

                $existingStages = $funnel->stages()->get()->keyBy('id');
                $stageIdsToKeep = [];

                collect($validated['stages'])
                    ->values()
                    ->each(function (array $stage, int $index) use ($funnel, $existingStages, &$stageIdsToKeep): void {
                        $stageId = isset($stage['id']) ? (int) $stage['id'] : null;
                        $existingStage = $stageId !== null && $existingStages->has($stageId)
                            ? $existingStages->get($stageId)
                            : null;

                        $payload = [
                            'name' => $stage['name'],
                            'stage_order' => $index + 1,
                            'conversion_rate' => $stage['conversion_rate'] ?? null,
                            'expected_volume' => $stage['expected_volume'] ?? null,
                            'meta' => $this->preserveDisabledBlocks(
                                $stage['meta'] ?? null,
                                $existingStage instanceof FunnelStage && is_array($existingStage->meta) ? $existingStage->meta : null,
                                $funnel->id,
                                $existingStage instanceof FunnelStage ? $existingStage->id : null,
                            ),
                        ];

                        if ($existingStage instanceof FunnelStage) {
                            $existingStage->update($payload);
                            $stageIdsToKeep[] = $existingStage->id;

                            return;
                        }

                        $createdStage = $funnel->stages()->create($payload);
                        $stageIdsToKeep[] = $createdStage->id;
                    });

                $funnel->stages()
                    ->whereNotIn('id', $stageIdsToKeep)
                    ->delete();
            });

            $funnel->refresh();
            $this->pruneRemovedManagedMedia($funnel->id, $previousReferenceKeys, 'builder_update', $funnel);
        } catch (Throwable $exception) {
            OperationalTelemetry::error('funnel.builder_save_failed', [
                'funnel_id' => $funnel->id,
                'user_id' => $request->user()?->id,
                'stage_count' => count($validated['stages'] ?? []),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return back()
                ->withErrors([
                    'save' => 'Nao foi possivel salvar o funil agora. Tente novamente em instantes.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('funnels.builder', $funnel)
            ->with('status', 'funnel-updated');
    }

    public function uploadMedia(UploadFunnelMediaRequest $request, Funnel $funnel): JsonResponse
    {
        $validated = $request->validated();
        $kind = (string) $validated['kind'];
        $file = $request->file('file');

        $directory = "funnels/{$funnel->id}/media/{$kind}";
        $disk = $this->managedMedia->diskForKind($kind);
        $path = $file->store($directory, $disk);
        $relativeUrl = $this->managedMedia->publicUrl($disk, $path);

        return response()->json([
            'url' => $relativeUrl,
            'path' => $path,
            'kind' => $kind,
            'disk' => $disk,
        ]);
    }

    public function showMedia(string $path): BinaryFileResponse
    {
        $normalizedPath = $this->normalizePublicMediaPath($path);

        abort_unless($normalizedPath !== null && Storage::disk('public')->exists($normalizedPath), 404);

        return response()->file(Storage::disk('public')->path($normalizedPath), [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function share(ShareFunnelRequest $request, Funnel $funnel): RedirectResponse
    {
        abort_unless($funnel->canShare($request->user()), 403);

        $validated = $request->validated();

        $targetUser = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        if ($targetUser->is($request->user())) {
            return back()->withErrors([
                'email' => 'Voce nao pode compartilhar com sua propria conta.',
            ]);
        }

        $funnel->sharedUsers()->syncWithoutDetaching([$targetUser->id]);
        $funnel->sharedUsers()->updateExistingPivot($targetUser->id, [
            'role' => $validated['role'],
            'shared_by_user_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'funnel-shared');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreFunnelId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'funnel';
        }

        $slug = $base;
        $suffix = 1;

        while (
            Funnel::query()
                ->when($ignoreFunnelId !== null, static fn ($query) => $query->whereKeyNot($ignoreFunnelId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    private function generateUniqueTemplateSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'template';
        }

        $slug = $base;
        $suffix = 1;

        while (FunnelTemplate::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-v{$suffix}";
        }

        return $slug;
    }

    private function duplicateFunnelName(string $name): string
    {
        $trimmed = trim($name);

        return $trimmed === '' ? 'Funil copia' : "{$trimmed} Copia";
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>|null
     */
    private function sanitizeStageMeta(?array $meta, ?FunnelStage $stage = null): ?array
    {
        if (!is_array($meta)) {
            return null;
        }

        $builder = is_array($meta['builder'] ?? null) ? $meta['builder'] : null;

        if ($builder === null || !is_array($builder['blocks'] ?? null)) {
            return $meta;
        }

        $disabledBlockCount = collect($builder['blocks'])
            ->filter(static fn (mixed $block): bool => is_array($block) && (($block['type'] ?? null) === 'charts'))
            ->count();

        $builder['blocks'] = collect($builder['blocks'])
            ->filter(static fn (mixed $block): bool => is_array($block) && (($block['type'] ?? null) !== 'charts'))
            ->values()
            ->all();

        if ($disabledBlockCount > 0) {
            OperationalTelemetry::warning('funnel.legacy_blocks_hidden', [
                'funnel_id' => $stage?->funnel_id,
                'stage_id' => $stage?->id,
                'block_type' => 'charts',
                'block_count' => $disabledBlockCount,
            ]);
        }

        $meta['builder'] = $builder;

        return $meta;
    }

    /**
     * @param  array<string, mixed>|null  $submittedMeta
     * @param  array<string, mixed>|null  $existingMeta
     * @return array<string, mixed>|null
     */
    private function preserveDisabledBlocks(
        ?array $submittedMeta,
        ?array $existingMeta,
        int $funnelId,
        ?int $stageId,
    ): ?array
    {
        if (!is_array($submittedMeta) || !is_array($existingMeta)) {
            return $submittedMeta;
        }

        $existingBlocks = is_array($existingMeta['builder']['blocks'] ?? null)
            ? $existingMeta['builder']['blocks']
            : [];
        $disabledBlocks = collect($existingBlocks)
            ->filter(static fn (mixed $block): bool => is_array($block) && (($block['type'] ?? null) === 'charts'))
            ->values()
            ->all();

        if ($disabledBlocks === []) {
            return $submittedMeta;
        }

        $builder = is_array($submittedMeta['builder'] ?? null) ? $submittedMeta['builder'] : [];
        $submittedBlocks = is_array($builder['blocks'] ?? null) ? $builder['blocks'] : [];
        $submittedBlockIds = collect($submittedBlocks)
            ->filter(static fn (mixed $block): bool => is_array($block))
            ->pluck('id')
            ->filter()
            ->all();

        $builder['blocks'] = array_merge(
            $submittedBlocks,
            collect($disabledBlocks)
                ->reject(static fn (array $block): bool => in_array($block['id'] ?? null, $submittedBlockIds, true))
                ->all(),
        );
        $submittedMeta['builder'] = $builder;

        OperationalTelemetry::info('funnel.legacy_blocks_preserved', [
            'funnel_id' => $funnelId,
            'stage_id' => $stageId,
            'block_type' => 'charts',
            'block_count' => count($disabledBlocks),
        ]);

        return $submittedMeta;
    }

    private function sanitizeLoadedFunnelStages(Funnel $funnel): void
    {
        $funnel->stages->transform(function (FunnelStage $stage): FunnelStage {
            $stage->meta = $this->sanitizeStageMeta(is_array($stage->meta) ? $stage->meta : null, $stage);

            return $stage;
        });
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, mixed>  $overrides
     */
    private function createFunnelFromBlueprint(User $user, array $blueprint, array $overrides = []): Funnel
    {
        $name = trim((string) (array_key_exists('name', $overrides) ? $overrides['name'] : ($blueprint['name'] ?? 'Funil sem nome')));
        $description = trim((string) (array_key_exists('description', $overrides) ? $overrides['description'] : ($blueprint['description'] ?? '')));
        $targetLeads = array_key_exists('target_leads', $overrides) ? $overrides['target_leads'] : ($blueprint['target_leads'] ?? null);
        $isActive = (bool) (array_key_exists('is_active', $overrides) ? $overrides['is_active'] : ($blueprint['is_active'] ?? true));
        $customDomain = $this->sanitizeCustomDomain(array_key_exists('custom_domain', $overrides) ? $overrides['custom_domain'] : ($blueprint['custom_domain'] ?? null));
        $designSettings = $this->sanitizeDesignSettings($blueprint['design_settings'] ?? null);
        $stages = collect((array) ($blueprint['stages'] ?? []))
            ->filter(static fn ($stage): bool => is_array($stage))
            ->values()
            ->map(function (array $stage, int $index): array {
                return [
                    'name' => trim((string) ($stage['name'] ?? 'Etapa ' . ($index + 1))),
                    'stage_order' => $index + 1,
                    'conversion_rate' => isset($stage['conversion_rate']) ? (float) $stage['conversion_rate'] : null,
                    'expected_volume' => isset($stage['expected_volume']) ? (int) $stage['expected_volume'] : null,
                    'meta' => is_array($stage['meta'] ?? null) ? $stage['meta'] : null,
                ];
            })
            ->filter(static fn (array $stage): bool => $stage['name'] !== '')
            ->all();

        if (count($stages) < 2) {
            throw ValidationException::withMessages([
                'stages' => 'Defina ao menos duas etapas para o funil.',
            ]);
        }

        $funnel = $user->funnels()->create([
            'name' => $name,
            'slug' => $this->generateUniqueSlug($name),
            'custom_domain' => $customDomain,
            'description' => $description !== '' ? $description : null,
            'target_leads' => $targetLeads !== null ? (int) $targetLeads : null,
            'is_active' => $isActive,
            'design_settings' => $designSettings,
        ]);

        $funnel->stages()->createMany($stages);

        return $funnel;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFunnelForTransfer(Funnel $funnel): array
    {
        $funnel->loadMissing([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);

        return [
            'name' => $funnel->name,
            'description' => $funnel->description,
            'target_leads' => $funnel->target_leads,
            'is_active' => $funnel->is_active,
            'custom_domain' => $funnel->custom_domain,
            'design_settings' => $this->sanitizeDesignSettings($funnel->design_settings),
            'stages' => $funnel->stages
                ->sortBy('stage_order')
                ->values()
                ->map(function (FunnelStage $stage): array {
                    return [
                        'name' => $stage->name,
                        'conversion_rate' => $stage->conversion_rate,
                        'expected_volume' => $stage->expected_volume,
                        'meta' => is_array($stage->meta) ? $stage->meta : null,
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @param  mixed  $decoded
     * @return array<string, mixed>
     */
    private function normalizeImportedFunnelBlueprint(mixed $decoded): array
    {
        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo JSON esta invalido.',
            ]);
        }

        $funnel = is_array($decoded['funnel'] ?? null) ? $decoded['funnel'] : $decoded;
        $stages = collect((array) ($funnel['stages'] ?? []))
            ->filter(static fn ($stage): bool => is_array($stage))
            ->values()
            ->all();

        if (count($stages) < 2) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo precisa conter ao menos duas etapas validas.',
            ]);
        }

        return [
            'name' => trim((string) ($funnel['name'] ?? 'Funil importado')),
            'description' => trim((string) ($funnel['description'] ?? '')),
            'target_leads' => isset($funnel['target_leads']) ? (int) $funnel['target_leads'] : null,
            'is_active' => (bool) ($funnel['is_active'] ?? false),
            'custom_domain' => null,
            'design_settings' => is_array($funnel['design_settings'] ?? null) ? $funnel['design_settings'] : null,
            'stages' => $stages,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTemplateSchemaFromFunnel(Funnel $funnel): array
    {
        $serialized = $this->serializeFunnelForTransfer($funnel);

        return [
            'target_leads' => $serialized['target_leads'] ?? null,
            'design_settings' => $serialized['design_settings'] ?? [],
            'preview' => $this->buildTemplatePreview($funnel),
            'stages' => $serialized['stages'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTemplatePreview(Funnel $funnel): array
    {
        $funnel->loadMissing([
            'stages' => static fn ($query) => $query->orderBy('stage_order'),
        ]);

        $firstStage = $funnel->stages->sortBy('stage_order')->first();
        $firstStageMeta = is_array($firstStage?->meta) ? $firstStage->meta : [];
        $builder = is_array($firstStageMeta['builder'] ?? null) ? $firstStageMeta['builder'] : [];
        $design = $this->sanitizeDesignSettings($funnel->design_settings);
        $blocks = collect((array) ($builder['blocks'] ?? []))
            ->filter(static fn ($block): bool => is_array($block))
            ->values();
        $headline = $blocks
            ->first(static function (array $block): bool {
                return ($block['type'] ?? null) === 'content_text'
                    && trim((string) ($block['placeholder'] ?? '')) !== '';
            });
        $headlineText = null;

        if (is_array($headline)) {
            $headlineText = preg_replace(
                '/\s+/',
                ' ',
                trim(strip_tags(str_replace(['</p>', '</h1>', '</h2>', '</h3>', '<br>', '<br/>', '<br />'], ' ', (string) ($headline['placeholder'] ?? ''))))
            );
        }

        if ($headlineText === null || $headlineText === '') {
            $headlineText = trim((string) ($funnel->description ?? ''));
        }

        if ($headlineText === '') {
            $headlineText = 'Template salvo a partir do seu funil.';
        }

        $chips = $blocks
            ->filter(static fn ($block): bool => is_array($block))
            ->take(3)
            ->map(static fn (array $block): string => Str::headline((string) ($block['type'] ?? 'Bloco')))
            ->filter(static fn (string $label): bool => $label !== '')
            ->values()
            ->all();

        return [
            'badge' => 'Personalizado',
            'accentColor' => (string) ($design['accentColor'] ?? '#3d8bff'),
            'headline' => $headlineText,
            'chips' => $chips,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function assignableUsersForFunnel(Funnel $funnel): \Illuminate\Database\Eloquent\Collection
    {
        $funnel->loadMissing(['user:id,name', 'sharedUsers:id,name']);

        $users = collect();

        if ($funnel->user !== null) {
            $users->push($funnel->user);
        }

        $funnel->sharedUsers
            ->filter(static fn (User $user): bool => $user->pivot?->role === Funnel::SHARE_ROLE_EDITOR)
            ->each(static fn (User $user) => $users->push($user));

        return new \Illuminate\Database\Eloquent\Collection(
            $users->unique('id')->sortBy('name')->values()->all(),
        );
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>
     */
    private function resolveDesignSettings(?array $settings): array
    {
        return $this->sanitizeDesignSettings($settings);
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @param  array<string, mixed>  $existingSettings
     * @return array<string, mixed>
     */
    private function sanitizeDesignSettings(?array $settings, array $existingSettings = []): array
    {
        $merged = array_merge([
            'alignment' => 'center',
            'width' => 'small',
            'elementSize' => 'default',
            'spacing' => 'default',
            'radius' => 'medium',
            'showLogo' => true,
            'showProgress' => true,
            'allowBack' => true,
            'accentColor' => '#3d8bff',
            'pageColor' => '#050d22',
            'cardColor' => '#0b1a3a',
            'headingColor' => '#f8fbff',
            'textColor' => '#a8bfeb',
            'buttonColor' => '#12356f',
            'buttonTextColor' => '#e8f2ff',
            'fontStyle' => 'modern',
            'logoUrl' => '',
            'faviconUrl' => '',
            'seoTitle' => '',
            'seoDescription' => '',
            'seoImageUrl' => '',
            'unavailableTitle' => 'Funil indisponivel',
            'unavailableDescription' => 'Este funil nao esta disponivel no momento.',
            'expiresAt' => null,
            'completion_page' => [
                'enabled' => false,
                'title' => 'Resposta enviada',
                'description' => 'Obrigado. Recebemos suas respostas e em breve entraremos em contato.',
                'image_url' => '',
                'primary_button_text' => 'Voltar ao inicio',
                'primary_button_url' => '/',
                'primary_button_new_tab' => false,
                'secondary_button_text' => '',
                'secondary_button_url' => '',
                'secondary_button_new_tab' => false,
                'auto_redirect_url' => '',
                'auto_redirect_delay_seconds' => 0,
            ],
        ], $existingSettings, is_array($settings) ? $settings : []);

        $merged['logoUrl'] = trim((string) ($merged['logoUrl'] ?? ''));
        $merged['faviconUrl'] = trim((string) ($merged['faviconUrl'] ?? ''));
        $merged['seoTitle'] = trim((string) ($merged['seoTitle'] ?? ''));
        $merged['seoDescription'] = trim((string) ($merged['seoDescription'] ?? ''));
        $merged['seoImageUrl'] = trim((string) ($merged['seoImageUrl'] ?? ''));
        $merged['unavailableTitle'] = trim((string) ($merged['unavailableTitle'] ?? '')) ?: 'Funil indisponivel';
        $merged['unavailableDescription'] = trim((string) ($merged['unavailableDescription'] ?? '')) ?: 'Este funil nao esta disponivel no momento.';
        $merged['expiresAt'] = ($expiresAt = trim((string) ($merged['expiresAt'] ?? ''))) !== '' ? $expiresAt : null;
        $merged['tokens'] = FunnelDesignTokens::resolve($merged);

        return $merged;
    }

    /**
     * @param  array<string, mixed>|null  $completionPage
     * @return array<string, mixed>
     */
    private function sanitizeCompletionPage(?array $completionPage): array
    {
        return [
            'enabled' => (bool) ($completionPage['enabled'] ?? false),
            'title' => trim((string) ($completionPage['title'] ?? '')),
            'description' => trim((string) ($completionPage['description'] ?? '')),
            'image_url' => trim((string) ($completionPage['image_url'] ?? '')),
            'primary_button_text' => trim((string) ($completionPage['primary_button_text'] ?? '')),
            'primary_button_url' => trim((string) ($completionPage['primary_button_url'] ?? '')),
            'primary_button_new_tab' => (bool) ($completionPage['primary_button_new_tab'] ?? false),
            'secondary_button_text' => trim((string) ($completionPage['secondary_button_text'] ?? '')),
            'secondary_button_url' => trim((string) ($completionPage['secondary_button_url'] ?? '')),
            'secondary_button_new_tab' => (bool) ($completionPage['secondary_button_new_tab'] ?? false),
            'auto_redirect_url' => trim((string) ($completionPage['auto_redirect_url'] ?? '')),
            'auto_redirect_delay_seconds' => max(0, (int) ($completionPage['auto_redirect_delay_seconds'] ?? 0)),
        ];
    }

    private function sanitizeCustomDomain(mixed $value): ?string
    {
        $domain = strtolower(trim((string) $value));

        return $domain !== '' ? $domain : null;
    }

    /**
     * @param  list<string>  $previousReferenceKeys
     */
    private function pruneRemovedManagedMedia(int $funnelId, array $previousReferenceKeys, string $reason, ?Funnel $funnel = null): void
    {
        $currentReferenceKeys = $funnel !== null
            ? $this->managedMedia->referencedKeysForFunnel($funnel)
            : [];

        $removedReferenceKeys = array_values(array_diff($previousReferenceKeys, $currentReferenceKeys));

        if ($removedReferenceKeys === []) {
            return;
        }

        $deletedReferenceKeys = $this->managedMedia->pruneUnusedReferenceKeys($removedReferenceKeys);

        if ($deletedReferenceKeys === []) {
            return;
        }

        OperationalTelemetry::info('funnel.media_pruned', [
            'funnel_id' => $funnelId,
            'reason' => $reason,
            'deleted_reference_keys' => $deletedReferenceKeys,
        ]);
    }

    private function publicMediaUrl(string $path): string
    {
        return '/media/' . ltrim($path, '/');
    }

    private function normalizePublicMediaPath(string $path): ?string
    {
        $normalizedPath = trim(urldecode($path));

        if ($normalizedPath === '') {
            return null;
        }

        if (str_starts_with($normalizedPath, '/storage/')) {
            $normalizedPath = ltrim(substr($normalizedPath, strlen('/storage/')), '/');
        } elseif (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = ltrim(substr($normalizedPath, strlen('storage/')), '/');
        }

        if (str_starts_with($normalizedPath, '/media/')) {
            $normalizedPath = ltrim(substr($normalizedPath, strlen('/media/')), '/');
        } elseif (str_starts_with($normalizedPath, 'media/')) {
            $normalizedPath = ltrim(substr($normalizedPath, strlen('media/')), '/');
        }

        if (str_contains($normalizedPath, '..')) {
            return null;
        }

        return ltrim($normalizedPath, '/');
    }
}
