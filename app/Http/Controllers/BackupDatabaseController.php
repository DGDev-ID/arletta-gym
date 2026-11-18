<?php

namespace App\Http\Controllers;

use App\Helpers\S3Helper;
use App\Jobs\BackupDatabase as BackupDatabaseJob;
use App\Models\BackupDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BackupDatabaseController extends Controller
{
    /**
     * Display a listing of database backups.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $backups = BackupDatabase::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (BackupDatabase $backup) => [
                'id'                   => $backup->id,
                'name'                 => $backup->name,
                'url'                  => $backup->url,
                'created_at'           => $backup->created_at?->format('Y-m-d H:i:s'),
                'created_at_formatted' => $backup->created_at?->translatedFormat('d M Y, H:i') . ' WIB',
                'created_at_diff'      => $backup->created_at?->diffForHumans(),
            ]);

        return Inertia::render('BackupDatabase/Index', [
            'backups' => $backups,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Dispatch the database backup job.
     */
    public function store(Request $request): RedirectResponse
    {
        BackupDatabaseJob::dispatch();

        return back()->with('success', 'Job Backup Database berhasil di-dispatch. Proses backup pgsql sedang berjalan di latar belakang.');
    }

    /**
     * Legacy dispatch endpoint alias.
     */
    public function dispatch(Request $request): RedirectResponse
    {
        return $this->store($request);
    }

    /**
     * Delete a database backup entry.
     */
    public function destroy(Request $request, BackupDatabase $backupDatabase): RedirectResponse
    {
        // Clean local public storage if exists
        $localPublicPath = 'backup_databases/' . $backupDatabase->name;
        if (Storage::disk('public')->exists($localPublicPath)) {
            Storage::disk('public')->delete($localPublicPath);
        }

        $backupDatabase->delete();

        return back()->with('success', 'Data backup database berhasil dihapus.');
    }

    /**
     * Download or redirect to the backup file.
     */
    public function download(Request $request, BackupDatabase $backupDatabase)
    {
        if (config('services.supabase.url') && config('services.supabase.key')) {
            $signedUrl = S3Helper::getSignedUrl('backup_databases/' . $backupDatabase->name, 3600);
            return redirect()->away($signedUrl);
        }

        $localPublicPath = 'backup_databases/' . $backupDatabase->name;
        if (Storage::disk('public')->exists($localPublicPath)) {
            return Storage::disk('public')->download($localPublicPath, $backupDatabase->name);
        }

        return redirect()->away($backupDatabase->url);
    }
}
