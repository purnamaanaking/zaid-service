<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEventLink extends Model
{
    use HasUuids;

    protected $fillable = [
        'task_id',
        'user_calendar_connection_id',
        'link_type',
        'google_event_id',
        'google_event_etag',
        'remote_status',
        'remote_updated_at',
        'last_synced_at',
        'last_synced_payload_hash',
        'sync_status',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'remote_updated_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function userCalendarConnection(): BelongsTo
    {
        return $this->belongsTo(UserCalendarConnection::class);
    }
}
