<?php

namespace App\Jobs;

use App\Models\FunnelSubmission;
use App\Support\OperationalTelemetry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFunnelSubmissionJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $submissionId,
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submission = FunnelSubmission::query()
            ->withCount('answers')
            ->find($this->submissionId);

        if (!$submission instanceof FunnelSubmission) {
            OperationalTelemetry::warning('funnel_submission.process_missing', [
                'submission_id' => $this->submissionId,
            ]);

            return;
        }

        $meta = is_array($submission->meta) ? $submission->meta : [];
        $meta['processed_at'] = now()->toISOString();
        $meta['answers_count'] = $submission->answers_count;
        $timeline = collect($meta['timeline'] ?? [])
            ->filter(static fn ($event): bool => is_array($event))
            ->values();
        $timeline->prepend([
            'id' => (string) str()->uuid(),
            'type' => 'processed',
            'source' => 'queue',
            'actor_name' => 'Sistema',
            'title' => 'Lead processado',
            'description' => 'Contagem de respostas atualizada automaticamente.',
            'created_at' => now()->toISOString(),
        ]);
        $meta['timeline'] = $timeline
            ->take(50)
            ->values()
            ->all();

        $submission->update(['meta' => $meta]);

        OperationalTelemetry::info('funnel_submission.processed', [
            'submission_id' => $submission->id,
            'funnel_id' => $submission->funnel_id,
            'answers_count' => $submission->answers_count,
        ]);
    }
}
