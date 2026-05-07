<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptAction extends Model
{
    use HasUuids;

    protected $fillable = [
        'prompt_request_id',
        'action_type',
        'target_entity_type',
        'target_entity_id',
        'execution_order',
        'status',
        'payload',
        'result_payload',
    ];

    protected function casts(): array
    {
        return [
            'execution_order' => 'integer',
            'payload' => 'array',
            'result_payload' => 'array',
        ];
    }

    public function promptRequest(): BelongsTo
    {
        return $this->belongsTo(PromptRequest::class);
    }
}
