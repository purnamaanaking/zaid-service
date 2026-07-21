<?php

namespace App\Services\Whatsapp;

use App\Models\User;
use App\Services\Prompt\PromptCommandService;

class WhatsappAgentService
{
    public function __construct(private readonly PromptCommandService $prompts) {}

    /** @param array<int, array{type: string, url?: string, data_url?: string, mime_type?: string, text?: string}>|null $attachments */
    public function handle(User $user, string $text, string $channel = 'whatsapp', ?array $attachments = null): array
    {
        $result = $this->prompts->process($user, $text, $channel, $attachments);
        return ['prompt_request_id' => $result['prompt_request_id'], 'human_response' => $result['human_response']];
    }
}
