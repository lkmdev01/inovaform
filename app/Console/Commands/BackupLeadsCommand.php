<?php

namespace App\Console\Commands;

use App\Models\FunnelSubmission;
use App\Support\OperationalTelemetry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CSV backup with funnel leads submissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = 'backups/leads';
        $filename = $directory . '/leads-' . now()->format('Ymd-His') . '.csv';
        Storage::makeDirectory($directory);

        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            $this->error('Nao foi possivel iniciar stream de backup.');

            return self::FAILURE;
        }

        fputcsv($stream, [
            'ID',
            'FUNNEL_ID',
            'STATUS',
            'LEAD_NAME',
            'LEAD_EMAIL',
            'LEAD_PHONE',
            'SUBMITTED_AT',
            'CREATED_AT',
        ]);

        FunnelSubmission::query()
            ->latest('id')
            ->chunk(1000, static function ($rows) use ($stream): void {
                foreach ($rows as $submission) {
                    fputcsv($stream, [
                        $submission->id,
                        $submission->funnel_id,
                        $submission->status,
                        $submission->lead_name,
                        $submission->lead_email,
                        $submission->lead_phone,
                        optional($submission->submitted_at)?->format('Y-m-d H:i:s'),
                        optional($submission->created_at)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

        rewind($stream);
        $contents = stream_get_contents($stream) ?: '';
        fclose($stream);

        Storage::put($filename, $contents);

        OperationalTelemetry::info('leads.backup.created', [
            'path' => $filename,
            'bytes' => strlen($contents),
        ]);

        $this->info("Backup criado: {$filename}");

        return self::SUCCESS;
    }
}
