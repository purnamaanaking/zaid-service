<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'theme',
        'timezone',
        'default_task_time',
        'reminder_offset_minutes',
        'reminder_enabled',
    ];

    protected function casts(): array
    {
        return [
            'reminder_offset_minutes' => 'integer',
            'reminder_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
