<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiPromptParser implements PromptParser
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a task and calendar assistant that understands Indonesian natural language.
Your job is to parse the user's message into a structured JSON command.

You MUST respond with valid JSON only. No explanation, no markdown, no code block.

JSON format:
{
  "intent": "CREATE" | "READ" | "UPDATE" | "DELETE",
  "confidence_score": 0.0 to 1.0,
  "parse_status": "parsed" | "ambiguous" | "unsupported" | "failed",
  "requires_confirmation": true | false,
  "entities": {
    "entity_type": "task",
    "title": "string or null",
    "scheduled_date": "YYYY-MM-DD or null",
    "scheduled_time": "HH:MM:SS or null",
    "all_day": true | false,
    "recurrence": {
      "type": "daily" | "weekly" | "monthly" | null,
      "day_of_week": "monday" | "tuesday" | "wednesday" | "thursday" | "friday" | "saturday" | "sunday" | null,
      "interval": 1
    } | null,
    "description": "string or null",
    "search_query": "string or null"
  }
}

Rules:
- TODAY is provided. Use it for relative dates like "hari ini", "besok", "minggu depan", "lusa".
- "Setiap Jumat" = weekly recurrence on friday.
- "Setiap hari" = daily recurrence.
- If intent is READ, set search_query to capture the user's filter criteria.
- If command is unclear or ambiguous, set requires_confirmation to true and parse_status to "ambiguous".
- If command is not task/calendar related, set parse_status to "unsupported".
- If an image is attached, analyze the image for any schedule, task, or calendar information and extract it.
- If a voice transcription is provided, parse the transcription as the user's command.
PROMPT;

    public function __construct(
        private readonly string $modelText,
        private readonly string $modelMultimodal,
        private readonly string $apiKey,
        private readonly string $apiBase,
    ) {}

    public function parse(string $text, string $userId, ?array $attachments = null): array
    {
        $hasMedia = ! empty($attachments);
        $model = $hasMedia ? $this->modelMultimodal : $this->modelText;
        $today = now()->format('Y-m-d');

        $userContent = $this->buildUserContent($text, $attachments);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$this->apiBase}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => self::SYSTEM_PROMPT."\n\nTODAY = {$today}",
                        ],
                        [
                            'role' => 'user',
                            'content' => $userContent,
                        ],
                    ],
                    'max_tokens' => 512,
                    'temperature' => 0.1,
                ]);

            if (! $response->successful()) {
                Log::error('Prompt parser API failed.', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $userId,
                ]);

                return $this->failedResult();
            }

            $content = $response->json('choices.0.message.content', '{}');

            $content = preg_replace('/^```(?:json)?\s*/', '', trim($content));
            $content = preg_replace('/\s*```$/', '', $content);

            $parsed = json_decode($content, true);

            if (! is_array($parsed) || ! isset($parsed['intent'])) {
                Log::warning('Prompt parser returned invalid JSON.', [
                    'model' => $model,
                    'raw' => $content,
                    'user_id' => $userId,
                ]);

                return $this->failedResult();
            }

            return $parsed;
        } catch (Throwable $e) {
            Log::error('Prompt parser error.', [
                'model' => $model,
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return $this->failedResult();
        }
    }

    /**
     * @param  array<int, array{type: string, url?: string, mime_type?: string}>|null  $attachments
     * @return string|array<int, array<string, mixed>>
     */
    private function buildUserContent(string $text, ?array $attachments): string|array
    {
        if (empty($attachments)) {
            return $text;
        }

        $parts = [];

        $parts[] = [
            'type' => 'text',
            'text' => $text,
        ];

        foreach ($attachments as $attachment) {
            if ($attachment['type'] === 'image' && isset($attachment['url'])) {
                $parts[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $attachment['url'],
                    ],
                ];
            }

            if ($attachment['type'] === 'audio_transcription' && isset($attachment['text'])) {
                $parts[] = [
                    'type' => 'text',
                    'text' => "[Voice message transcription]: {$attachment['text']}",
                ];
            }
        }

        return $parts;
    }

    private function failedResult(): array
    {
        return [
            'intent' => 'READ',
            'confidence_score' => 0.0,
            'parse_status' => 'failed',
            'requires_confirmation' => true,
            'entities' => [
                'entity_type' => 'task',
                'title' => null,
                'scheduled_date' => null,
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => null,
            ],
        ];
    }
}
