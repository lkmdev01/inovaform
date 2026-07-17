<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLeadDetailsRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Funnel;
use App\Models\FunnelSubmission;
use App\Models\User;
use App\Support\OperationalTelemetry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FunnelSubmission::query()
            ->whereHas('funnel', function (Builder $builder) use ($request): void {
                $this->applyFunnelAccessConstraint($builder, $request->user()?->id);
            })
            ->with([
                'funnel:id,name,slug',
                'answers.stage:id,name',
            ]);

        $this->applyFilters($query, $request);

        $leads = $query
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FunnelSubmission $submission): array => $this->mapLeadSubmission($submission));

        $funnels = Funnel::query()
            ->where(function (Builder $builder) use ($request): void {
                $this->applyFunnelAccessConstraint($builder, $request->user()?->id);
            })
            ->select(['id', 'name', 'slug'])
            ->orderBy('name')
            ->get();

        return Inertia::render('leads/Index', [
            'leads' => $leads,
            'filters' => [
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status'),
                'funnel_id' => (string) $request->string('funnel_id'),
                'tag' => (string) $request->string('tag'),
                'assignee_id' => (string) $request->string('assignee_id'),
                'priority' => (string) $request->string('priority'),
                'has_notes' => (string) $request->string('has_notes'),
            ],
            'funnels' => $funnels,
            'assigneeOptions' => $this->assignableUsersForAccessibleFunnels($request->user()?->id)
                ->map(static fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                ])
                ->values()
                ->all(),
            'statusOptions' => [
                ['value' => 'new', 'label' => 'Novo'],
                ['value' => 'contacted', 'label' => 'Contatado'],
                ['value' => 'qualified', 'label' => 'Qualificado'],
                ['value' => 'lost', 'label' => 'Perdido'],
            ],
            'priorityOptions' => [
                ['value' => 'low', 'label' => 'Baixa'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'Alta'],
                ['value' => 'urgent', 'label' => 'Urgente'],
            ],
        ]);
    }

    public function show(Request $request, FunnelSubmission $lead): Response
    {
        abort_unless($lead->funnel && $lead->funnel->canManageLeads($request->user()), 403);

        $lead->loadMissing([
            'funnel:id,name,slug',
            'answers.stage:id,name',
        ]);

        OperationalTelemetry::info('lead.viewed', [
            'lead_id' => $lead->id,
            'funnel_id' => $lead->funnel_id,
            'actor_id' => $request->user()?->id,
        ]);

        return Inertia::render('leads/Show', [
            'lead' => $this->mapLeadSubmission($lead),
            'assigneeOptions' => $this->assignableUsersForFunnel($lead->funnel)
                ->map(static fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                ])
                ->values()
                ->all(),
            'statusOptions' => [
                ['value' => 'new', 'label' => 'Novo'],
                ['value' => 'contacted', 'label' => 'Contatado'],
                ['value' => 'qualified', 'label' => 'Qualificado'],
                ['value' => 'lost', 'label' => 'Perdido'],
            ],
            'priorityOptions' => [
                ['value' => 'low', 'label' => 'Baixa'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'Alta'],
                ['value' => 'urgent', 'label' => 'Urgente'],
            ],
        ]);
    }

    public function update(UpdateLeadDetailsRequest $request, FunnelSubmission $lead): RedirectResponse
    {
        $validated = $request->validated();
        $meta = is_array($lead->meta) ? $lead->meta : [];
        $previousStatus = $lead->status;
        $previousTags = array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), (array) ($meta['tags'] ?? [])),
            static fn (string $tag): bool => $tag !== ''
        ));
        $previousNotes = trim((string) ($meta['notes'] ?? ''));
        $previousAssigneeId = isset($meta['assignee_id']) ? (int) $meta['assignee_id'] : null;
        $previousAssigneeName = trim((string) ($meta['assignee_name'] ?? ''));
        $previousPriority = trim((string) ($meta['priority'] ?? 'normal')) ?: 'normal';
        $previousFollowUpAt = isset($meta['next_follow_up_at']) ? (string) $meta['next_follow_up_at'] : null;
        $statusChanged = false;
        $tagsChanged = false;
        $notesChanged = false;
        $assigneeChanged = false;
        $priorityChanged = false;
        $followUpChanged = false;
        $assignableUsers = $this->assignableUsersForFunnel($lead->funnel);

        if (array_key_exists('notes', $validated)) {
            $meta['notes'] = trim((string) ($validated['notes'] ?? ''));
            $notesChanged = $meta['notes'] !== $previousNotes;
        }

        if (array_key_exists('tags', $validated)) {
            $meta['tags'] = array_values(array_unique(array_filter(
                array_map(static fn (mixed $tag): string => trim((string) $tag), (array) ($validated['tags'] ?? [])),
                static fn (string $tag): bool => $tag !== ''
            )));
            $tagsChanged = $meta['tags'] !== $previousTags;
        }

        if (array_key_exists('status', $validated) && is_string($validated['status'])) {
            $lead->status = $validated['status'];
            $meta['last_status_change_at'] = now()->toISOString();
            $statusChanged = $lead->status !== $previousStatus;

            if (in_array($validated['status'], ['contacted', 'qualified'], true)) {
                $meta['last_contacted_at'] = now()->toISOString();
            }
        }

        if (array_key_exists('assignee_id', $validated)) {
            $assigneeId = $validated['assignee_id'] !== null ? (int) $validated['assignee_id'] : null;

            if ($assigneeId !== null && !$assignableUsers->contains(static fn (User $user): bool => $user->id === $assigneeId)) {
                return back()->withErrors([
                    'assignee_id' => 'Responsavel invalido para este funil.',
                ]);
            }

            $assignee = $assigneeId !== null ? $assignableUsers->firstWhere('id', $assigneeId) : null;
            $meta['assignee_id'] = $assignee?->id;
            $meta['assignee_name'] = $assignee?->name;
            $assigneeChanged = $previousAssigneeId !== $meta['assignee_id'] || $previousAssigneeName !== trim((string) ($meta['assignee_name'] ?? ''));
        }

        if (array_key_exists('priority', $validated)) {
            $meta['priority'] = trim((string) ($validated['priority'] ?? 'normal')) ?: 'normal';
            $priorityChanged = $meta['priority'] !== $previousPriority;
        }

        if (array_key_exists('next_follow_up_at', $validated)) {
            $meta['next_follow_up_at'] = $validated['next_follow_up_at'] ? Carbon::parse((string) $validated['next_follow_up_at'])->toISOString() : null;
            $followUpChanged = ($meta['next_follow_up_at'] ?? null) !== $previousFollowUpAt;
        }

        $timelineDescription = collect([
            $statusChanged ? "Status: {$previousStatus} -> {$lead->status}" : null,
            $tagsChanged ? 'Tags: ' . implode(', ', $meta['tags'] ?? []) : null,
            $notesChanged ? 'Notas atualizadas.' : null,
            $assigneeChanged ? 'Responsavel: ' . ($previousAssigneeName !== '' ? $previousAssigneeName : 'Nao definido') . ' -> ' . (trim((string) ($meta['assignee_name'] ?? '')) !== '' ? trim((string) ($meta['assignee_name'] ?? '')) : 'Nao definido') : null,
            $priorityChanged ? "Prioridade: {$previousPriority} -> {$meta['priority']}" : null,
            $followUpChanged ? 'Proximo follow-up: ' . ($meta['next_follow_up_at'] ?? 'limpo') : null,
        ])->filter()->implode(' | ');

        if ($timelineDescription !== '') {
            $meta = $this->appendTimelineEvent(
                $meta,
                'lead_updated',
                'Lead atualizado',
                $timelineDescription,
                'panel',
                (string) ($request->user()?->name ?? 'Equipe')
            );
        }

        $lead->meta = $meta;
        $lead->save();

        OperationalTelemetry::info('lead.updated', [
            'lead_id' => $lead->id,
            'funnel_id' => $lead->funnel_id,
            'actor_id' => $request->user()?->id,
            'status' => $lead->status,
            'tags_count' => count((array) ($meta['tags'] ?? [])),
            'assignee_id' => $meta['assignee_id'] ?? null,
            'priority' => $meta['priority'] ?? null,
        ]);

        return back()->with('status', 'lead-updated');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, FunnelSubmission $lead): RedirectResponse
    {
        $meta = is_array($lead->meta) ? $lead->meta : [];
        $previousStatus = $lead->status;
        $meta['last_status_change_at'] = now()->toISOString();

        if (in_array($request->validated('status'), ['contacted', 'qualified'], true)) {
            $meta['last_contacted_at'] = now()->toISOString();
        }

        $meta = $this->appendTimelineEvent(
            $meta,
            'status_changed',
            'Status alterado',
            "Status: {$previousStatus} -> " . (string) $request->validated('status'),
            'panel',
            (string) ($request->user()?->name ?? 'Equipe')
        );

        $lead->update([
            'status' => $request->validated('status'),
            'meta' => $meta,
        ]);

        OperationalTelemetry::info('lead.status_updated', [
            'lead_id' => $lead->id,
            'funnel_id' => $lead->funnel_id,
            'actor_id' => $request->user()?->id,
            'status' => $request->validated('status'),
        ]);

        return back()->with('status', 'lead-status-updated');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = FunnelSubmission::query()
            ->whereHas('funnel', function (Builder $builder) use ($request): void {
                $this->applyFunnelAccessConstraint($builder, $request->user()?->id);
            })
            ->with(['funnel:id,name']);

        $this->applyFilters($query, $request);

        $filename = 'leads-' . now()->format('Ymd-His') . '.csv';

        OperationalTelemetry::info('lead.export_requested', [
            'actor_id' => $request->user()?->id,
            'filters' => [
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status'),
                'funnel_id' => (string) $request->string('funnel_id'),
                'tag' => (string) $request->string('tag'),
                'assignee_id' => (string) $request->string('assignee_id'),
                'priority' => (string) $request->string('priority'),
                'has_notes' => (string) $request->string('has_notes'),
            ],
        ]);

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['ID', 'Funil', 'Nome', 'Email', 'Telefone', 'Status', 'Pontuacao', 'Responsavel', 'Prioridade', 'Proximo follow-up', 'Tags', 'Observacoes', 'Ultimo contato', 'Enviado em']);

            $query
                ->latest('submitted_at')
                ->chunk(500, static function ($rows) use ($handle): void {
                    foreach ($rows as $submission) {
                        $meta = is_array($submission->meta) ? $submission->meta : [];
                        fputcsv($handle, [
                            $submission->id,
                            $submission->funnel?->name ?? '',
                            $submission->lead_name ?? '',
                            $submission->lead_email ?? '',
                            $submission->lead_phone ?? '',
                            $submission->status,
                            (string) ($meta['score'] ?? 0),
                            (string) ($meta['assignee_name'] ?? ''),
                            (string) ($meta['priority'] ?? 'normal'),
                            (string) ($meta['next_follow_up_at'] ?? ''),
                            implode(', ', array_values(array_filter(array_map(static fn (mixed $tag): string => trim((string) $tag), (array) ($meta['tags'] ?? []))))),
                            trim((string) ($meta['notes'] ?? '')),
                            (string) ($meta['last_contacted_at'] ?? ''),
                            optional($submission->submitted_at)?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function applyFunnelAccessConstraint(Builder $builder, ?int $userId): void
    {
        if ($userId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder
            ->where('user_id', $userId)
            ->orWhereHas('sharedUsers', static function (Builder $sharedBuilder) use ($userId): void {
                $sharedBuilder
                    ->where('users.id', $userId)
                    ->where('funnel_user_shares.role', Funnel::SHARE_ROLE_EDITOR);
            });
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $search = trim((string) $request->string('q'));
        $status = trim((string) $request->string('status'));
        $funnelId = trim((string) $request->string('funnel_id'));
        $tag = trim((string) $request->string('tag'));
        $assigneeId = trim((string) $request->string('assignee_id'));
        $priority = trim((string) $request->string('priority'));
        $hasNotes = trim((string) $request->string('has_notes'));

        if ($search !== '') {
            $query->where(static function (Builder $builder) use ($search): void {
                $builder
                    ->where('lead_name', 'like', "%{$search}%")
                    ->orWhere('lead_email', 'like', "%{$search}%")
                    ->orWhere('lead_phone', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($funnelId !== '' && ctype_digit($funnelId)) {
            $query->where('funnel_id', (int) $funnelId);
        }

        if ($tag !== '') {
            $query->whereJsonContains('meta->tags', $tag);
        }

        if ($assigneeId !== '' && ctype_digit($assigneeId)) {
            $query->where('meta->assignee_id', (int) $assigneeId);
        }

        if ($priority !== '') {
            $query->where('meta->priority', $priority);
        }

        if ($hasNotes === 'yes') {
            $query->whereNotNull('meta->notes')->where('meta->notes', '!=', '');
        }
    }

    private function mapLeadSubmission(FunnelSubmission $submission): array
    {
        $meta = is_array($submission->meta) ? $submission->meta : [];

        return [
            'id' => $submission->id,
            'status' => $submission->status,
            'lead_name' => $submission->lead_name,
            'lead_email' => $submission->lead_email,
            'lead_phone' => $submission->lead_phone,
            'submitted_at' => optional($submission->submitted_at)?->toISOString(),
            'score' => is_numeric($meta['score'] ?? null) ? (float) $meta['score'] : 0.0,
            'tags' => array_values(array_filter(
                array_map(static fn (mixed $tag): string => trim((string) $tag), (array) ($meta['tags'] ?? [])),
                static fn (string $tag): bool => $tag !== ''
            )),
            'notes' => trim((string) ($meta['notes'] ?? '')),
            'last_contacted_at' => isset($meta['last_contacted_at']) ? (string) $meta['last_contacted_at'] : null,
            'next_follow_up_at' => isset($meta['next_follow_up_at']) ? (string) $meta['next_follow_up_at'] : null,
            'priority' => trim((string) ($meta['priority'] ?? 'normal')) ?: 'normal',
            'assignee' => [
                'id' => isset($meta['assignee_id']) ? (int) $meta['assignee_id'] : null,
                'name' => trim((string) ($meta['assignee_name'] ?? '')),
            ],
            'stage_values' => $submission->answers
                ->groupBy('funnel_stage_id')
                ->map(function ($stageAnswers): string {
                    return $stageAnswers
                        ->map(function ($answer): string {
                            $value = collect($answer->value ?? [])
                                ->map(static fn (mixed $item): string => trim((string) $item))
                                ->filter(static fn (string $item): bool => $item !== '')
                                ->implode(', ');

                            return trim((string) $answer->block_label) . ($value !== '' ? ': ' . $value : '');
                        })
                        ->filter(static fn (string $value): bool => $value !== '')
                        ->implode(' | ');
                })
                ->toArray(),
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
            'funnel' => [
                'id' => $submission->funnel?->id,
                'name' => $submission->funnel?->name,
                'slug' => $submission->funnel?->slug,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function appendTimelineEvent(array $meta, string $type, string $title, string $description = '', string $source = 'panel', string $actorName = 'Sistema'): array
    {
        $timeline = collect($meta['timeline'] ?? [])
            ->filter(static fn ($event): bool => is_array($event))
            ->values();

        $timeline->prepend([
            'id' => (string) str()->uuid(),
            'type' => $type,
            'source' => $source,
            'actor_name' => $actorName,
            'title' => $title,
            'description' => $description,
            'metadata' => [],
            'created_at' => now()->toISOString(),
        ]);

        $meta['timeline'] = $timeline
            ->take(50)
            ->values()
            ->all();

        return $meta;
    }

    /**
     * @return EloquentCollection<int, User>
     */
    private function assignableUsersForAccessibleFunnels(?int $userId): EloquentCollection
    {
        if ($userId === null) {
            return new EloquentCollection();
        }

        return User::query()
            ->where(function (Builder $builder) use ($userId): void {
                $builder->whereKey($userId)
                    ->orWhereHas('funnels', static function (Builder $funnelBuilder) use ($userId): void {
                        $funnelBuilder->where('user_id', $userId);
                    })
                    ->orWhereHas('sharedFunnels', static function (Builder $funnelBuilder) use ($userId): void {
                        $funnelBuilder
                            ->where('funnel_user_shares.shared_by_user_id', $userId)
                            ->where('funnel_user_shares.role', Funnel::SHARE_ROLE_EDITOR);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return EloquentCollection<int, User>
     */
    private function assignableUsersForFunnel(Funnel $funnel): EloquentCollection
    {
        $funnel->loadMissing(['user:id,name', 'sharedUsers:id,name']);

        $users = new EloquentCollection();

        if ($funnel->user !== null) {
            $users->push($funnel->user);
        }

        $funnel->sharedUsers
            ->filter(static fn (User $user): bool => $user->pivot?->role === Funnel::SHARE_ROLE_EDITOR)
            ->each(static fn (User $user) => $users->push($user));

        return $users
            ->unique('id')
            ->sortBy('name')
            ->values();
    }
}
