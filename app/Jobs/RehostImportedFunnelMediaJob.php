<?php

namespace App\Jobs;

use App\Models\Funnel;
use App\Support\InleadFunnelImporter;
use App\Support\OperationalTelemetry;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RehostImportedFunnelMediaJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public int $uniqueFor = 1800;

    public function __construct(public int $funnelId) {}

    public function uniqueId(): string
    {
        return (string) $this->funnelId;
    }

    public function handle(InleadFunnelImporter $importer): void
    {
        $funnel = Funnel::query()->find($this->funnelId);

        if (! $funnel instanceof Funnel) {
            OperationalTelemetry::warning('funnel.import_media_missing', [
                'funnel_id' => $this->funnelId,
            ]);

            return;
        }

        $total = $importer->remoteMediaCount($funnel);
        $importer->updateRemoteMediaStatus($this->funnelId, [
            'status' => 'processing',
            'total' => $total,
            'imported' => 0,
            'failed' => 0,
            'startedAt' => now()->toISOString(),
        ]);

        OperationalTelemetry::info('funnel.import_media_started', [
            'funnel_id' => $this->funnelId,
            'total' => $total,
        ]);

        $summary = $importer->rehostRemoteMedia($funnel);
        $status = $summary['failed'] > 0 ? 'partial' : 'completed';
        $importer->updateRemoteMediaStatus($this->funnelId, [
            'status' => $status,
            'total' => $total,
            'imported' => $summary['imported'],
            'failed' => $summary['failed'],
            'finishedAt' => now()->toISOString(),
        ]);

        OperationalTelemetry::info('funnel.import_media_finished', [
            'funnel_id' => $this->funnelId,
            'status' => $status,
            ...$summary,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $importer = app(InleadFunnelImporter::class);
        $importer->updateRemoteMediaStatus($this->funnelId, [
            'status' => 'failed',
            'total' => 0,
            'imported' => 0,
            'failed' => 0,
            'finishedAt' => now()->toISOString(),
        ]);

        OperationalTelemetry::error('funnel.import_media_failed', [
            'funnel_id' => $this->funnelId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
