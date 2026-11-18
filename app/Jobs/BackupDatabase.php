<?php

namespace App\Jobs;

use App\Helpers\S3Helper;
use App\Models\BackupDatabase as BackupDatabaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $dbHost     = config('database.connections.pgsql.host', '127.0.0.1');
        $dbPort     = config('database.connections.pgsql.port', '5432');
        $dbName     = config('database.connections.pgsql.database');
        $dbUser     = config('database.connections.pgsql.username');
        $dbPassword = config('database.connections.pgsql.password');

        $timestamp  = now()->format('Y-m-d_H-i-s');
        $fileName   = "backup_{$timestamp}.sql";
        $tempDir    = storage_path('app/temp');
        $tempPath   = "{$tempDir}/{$fileName}";

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Build pg_dump command (requires postgresql-client-18 on the server)
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -F p -f %s %s 2>&1',
            escapeshellarg($dbPassword),
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($tempPath),
            escapeshellarg($dbName)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $errorMsg = implode("\n", $output);
            Log::error('BackupDatabase: pg_dump failed', ['exit_code' => $exitCode, 'output' => $errorMsg]);
            throw new \Exception("pg_dump failed (exit {$exitCode}): {$errorMsg}");
        }

        // Store temp file in local storage so S3Helper can pick it up
        $localFileName = $fileName;
        Storage::disk('local')->put("temp/{$localFileName}", file_get_contents($tempPath));
        @unlink($tempPath);

        // Upload to Supabase S3
        $s3Path = S3Helper::storeFileToS3('backups', $localFileName);

        // Get public URL
        $url = S3Helper::getUrlFileS3('backups', $localFileName);

        // Cleanup local temp
        S3Helper::removeFileTemp($localFileName);

        // Save record
        BackupDatabaseModel::create([
            'name' => $fileName,
            'url'  => $url,
        ]);

        Log::info('BackupDatabase: backup berhasil', ['file' => $fileName, 'url' => $url]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BackupDatabase: job gagal', [
            'error' => $exception->getMessage(),
        ]);
    }
}
