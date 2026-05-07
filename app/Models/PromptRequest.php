<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'channel',
        'raw_text',
        'normalized_text',
        'intent',
        'confidence_score',
        'parse_status',
        'extracted_entities',
        'execution_summary',
        'execution_status',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'float',
            'extracted_entities' => 'array',
            'execution_summary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(PromptAction::class);
    }
}
