<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRecurrence extends Model
{
    use HasUuids;

    protected $fillable = [
        'task_id',
        'recurrence_type',
        'interval_value',
        'day_of_week',
        'day_of_month',
        'end_date',
        'occurrence_limit',
        'rrule_payload',
    ];

    protected function casts(): array
    {
        return [
            'interval_value' => 'integer',
            'day_of_month' => 'integer',
            'end_date' => 'date',
            'occurrence_limit' => 'integer',
            'rrule_payload' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
