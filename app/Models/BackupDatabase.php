<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupDatabase extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'name',
        'url',
        'status',
        'progress',
        'progress_message',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'progress'   => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
