<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'prompt_request_id',
        'direction',
        'wa_message_id',
        'sender_phone_e164',
        'recipient_phone_e164',
        'message_text',
        'webhook_payload',
        'processing_status',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'webhook_payload' => 'array',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function promptRequest(): BelongsTo
    {
        return $this->belongsTo(PromptRequest::class);
    }
}
