<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'task_list_id',
        'source_channel',
        'source_prompt_request_id',
        'google_task_list_id',
        'google_task_list_title',
        'title',
        'description',
        'status',
        'scheduled_date',
        'scheduled_time',
        'timezone',
        'all_day',
        'is_recurring',
        'completed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'all_day' => 'boolean',
            'is_recurring' => 'boolean',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(TaskRecurrence::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(TaskChange::class);
    }

    public function calendarEventLink(): HasOne
    {
        return $this->hasOne(CalendarEventLink::class);
    }
}
