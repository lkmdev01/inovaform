<?php

namespace App\Console\Commands;

use App\Models\Funnel;
use App\Models\FunnelSubmission;
use App\Support\ManagedMedia;
use App\Support\OperationalTelemetry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MonitorFunnelHealthCommand extends Command
{
    protected $signature = 'app:monitor-funnel-health';

    protected $description = 'Collect critical operational metrics for funnels and leads.';

    public function __construct(private readonly ManagedMedia $managedMedia)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $totalFunnels = Funnel::query()->count();
        $activeFunnels = Funnel::query()->where('is_active', true)->count();
        $recentLeads = FunnelSubmission::query()
            ->where('submitted_at', '>=', now()->subDay())
            ->count();
        $staleLeads = FunnelSubmission::query()
            ->whereIn('status', ['new', 'contacted'])
            ->where('submitted_at', '<=', now()->subDays(7))
            ->count();
        $mediaDisk = $this->managedMedia->mediaDisk();
        $mediaFilesTotal = collect(Storage::disk($mediaDisk)->allFiles('funnels'))
            ->filter(static fn (string $path): bool => str_contains($path, '/media/'))
            ->count();

        $context = [
            'funnels_total' => $totalFunnels,
            'funnels_active' => $activeFunnels,
            'recent_leads_24h' => $recentLeads,
            'stale_leads_7d' => $staleLeads,
            'media_disk' => $mediaDisk,
            'media_files_total' => $mediaFilesTotal,
        ];

        OperationalTelemetry::info('funnel.health_snapshot', $context);

        if ($staleLeads > 0) {
            OperationalTelemetry::warning('funnel.health_stale_leads_detected', [
                'stale_leads_7d' => $staleLeads,
            ]);
        }

        $this->table(
            ['Funis', 'Ativos', 'Leads 24h', 'Leads parados 7d', 'Arquivos de midia'],
            [[
                $totalFunnels,
                $activeFunnels,
                $recentLeads,
                $staleLeads,
                $mediaFilesTotal,
            ]]
        );

        return self::SUCCESS;
    }
}
