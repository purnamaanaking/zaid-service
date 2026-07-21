<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarSyncLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'calendar_event_id',
        'user_calendar_connection_id',
        'calendar_event_link_id',
        'direction',
        'action',
        'status',
        'context',
        'error_message',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userCalendarConnection(): BelongsTo
    {
        return $this->belongsTo(UserCalendarConnection::class);
    }

    public function calendarEventLink(): BelongsTo
    {
        return $this->belongsTo(CalendarEventLink::class);
    }
}
