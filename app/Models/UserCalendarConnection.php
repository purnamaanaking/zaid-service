<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCalendarConnection extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'provider',
        'google_calendar_id',
        'google_calendar_summary',
        'google_task_list_id',
        'encrypted_access_token',
        'encrypted_refresh_token',
        'token_expires_at',
        'scopes',
        'sync_token',
        'tasks_sync_token',
        'status',
        'last_synced_at',
        'last_error_at',
        'last_error_message',
        'watch_channel_id',
        'watch_resource_id',
        'watch_expiry',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'last_error_at' => 'datetime',
            'watch_expiry' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calendarEventLinks(): HasMany
    {
        return $this->hasMany(CalendarEventLink::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(CalendarSyncLog::class);
    }
}
