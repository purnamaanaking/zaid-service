<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleTaskList extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_calendar_connection_id',
        'google_task_list_id',
        'title',
        'is_default',
        'sync_token',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function userCalendarConnection(): BelongsTo
    {
        return $this->belongsTo(UserCalendarConnection::class);
    }
}
