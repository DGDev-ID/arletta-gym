<?php

namespace App\Http\Controllers;

use App\Jobs\BackupDatabase;
use App\Models\BackupDatabase as BackupDatabaseModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupDatabaseController extends Controller
{
    public function index()
    {
        $backups = BackupDatabaseModel::latest()->paginate(15);

        return Inertia::render('BackupDatabase/Index', [
            'backups' => $backups,
        ]);
    }

    public function dispatch()
    {
        BackupDatabase::dispatch();

        return back()->with('success', 'Backup database sedang diproses.');
    }
}
