<?php

namespace App\Http\Controllers;

use App\Models\Funnel;
use App\Models\FunnelTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $funnels = Funnel::query()
            ->whereBelongsTo($user)
            ->with([
                'stages' => static fn ($query) => $query->orderBy('stage_order'),
            ])
            ->latest()
            ->get();
        $funnels = $this->appendDashboardMetrics($funnels);

        $sharedFunnels = $user->sharedFunnels()
            ->with([
                'stages' => static fn ($query) => $query->orderBy('stage_order'),
                'user:id,name,email',
            ])
            ->withPivot(['role', 'shared_by_user_id'])
            ->latest('funnel_user_shares.created_at')
            ->get();
        $sharedFunnels = $this->appendDashboardMetrics($sharedFunnels);

        $maxFunnels = 5;
        $registeredLeads = (int) $funnels
            ->sum(static function (Funnel $funnel): int {
                return (int) data_get($funnel, 'dashboard_metrics.leads', 0);
            });
        $leadsQuota = 5000;
        $templates = FunnelTemplate::query()
            ->where('is_active', true)
            ->where(static function ($query) use ($user): void {
                $query
                    ->where('is_system', true)
                    ->orWhere('user_id', $user?->id);
            })
            ->orderByDesc('is_premium')
            ->orderByDesc('is_system')
            ->orderBy('sort_order')
            ->orderByDesc('version')
            ->get()
            ->map(static function (FunnelTemplate $template): array {
                $schema = is_array($template->schema) ? $template->schema : [];
                $preview = is_array($schema['preview'] ?? null) ? $schema['preview'] : [];

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'description' => $template->description,
                    'category' => $template->category,
                    'thumbnail_path' => $template->thumbnail_path,
                    'is_system' => $template->is_system,
                    'is_premium' => $template->is_premium,
                    'version' => $template->version,
                    'source_funnel_id' => $template->source_funnel_id,
                    'stage_count' => count((array) ($schema['stages'] ?? [])),
                    'preview' => [
                        'badge' => (string) ($preview['badge'] ?? 'Modelo'),
                        'accentColor' => (string) ($preview['accentColor'] ?? '#3d8bff'),
                        'headline' => trim((string) ($preview['headline'] ?? '')),
                        'chips' => array_values(array_filter(
                            array_map(static fn (mixed $value): string => trim((string) $value), (array) ($preview['chips'] ?? [])),
                            static fn (string $value): bool => $value !== ''
                        )),
                    ],
                ];
            })
            ->values();

        return Inertia::render('Dashboard', [
            'funnels' => $funnels,
            'sharedFunnels' => $sharedFunnels,
            'templates' => $templates,
            'templateCategories' => $templates
                ->pluck('category')
                ->filter(static fn ($category): bool => is_string($category) && trim($category) !== '')
                ->map(static fn ($category): string => (string) $category)
                ->unique()
                ->values(),
            'aiGenerationEnabled' => filled(config('services.groq.api_key')),
            'stats' => [
                'currentFunnels' => $funnels->count(),
                'maxFunnels' => $maxFunnels,
                'registeredLeads' => $registeredLeads,
                'leadsQuota' => $leadsQuota,
            ],
        ]);
    }

    /**
     * @param  Collection<int, Funnel>  $funnels
     * @return Collection<int, Funnel>
     */
    private function appendDashboardMetrics(Collection $funnels): Collection
    {
        return $funnels->map(function (Funnel $funnel): Funnel {
            $orderedStages = $funnel->stages->sortBy('stage_order')->values();
            $firstStage = $orderedStages->first();
            $lastStage = $orderedStages->last();

            $leads = $funnel->submissions()->count();

            $started = $firstStage
                ? $funnel->submissions()
                    ->whereHas('answers', static fn ($query) => $query->where('funnel_stage_id', $firstStage->id))
                    ->count()
                : $leads;

            $completed = $lastStage
                ? $funnel->submissions()
                    ->whereHas('answers', static fn ($query) => $query->where('funnel_stage_id', $lastStage->id))
                    ->count()
                : $leads;

            $conversion = $started > 0 ? round(($completed / $started) * 100, 2) : 0.0;

            $funnel->setAttribute('dashboard_metrics', [
                'leads' => $leads,
                'started' => $started,
                'completed' => $completed,
                'conversion' => $conversion,
            ]);

            return $funnel;
        });
    }
}
