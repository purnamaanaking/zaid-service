<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
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
PROMPT;

    public function __construct(private readonly string $model) {}

    public function parse(string $text, string $userId): array
    {
        $today = now()->format('Y-m-d');

        try {
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => self::SYSTEM_PROMPT."\n\nTODAY = {$today}",
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
                'max_tokens' => 512,
                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            $parsed = json_decode($content, true);

            if (! is_array($parsed) || ! isset($parsed['intent'])) {
                Log::warning('OpenAI parser returned invalid JSON.', [
                    'raw' => $content,
                    'user_id' => $userId,
                ]);

                return $this->failedResult();
            }

            return $parsed;
        } catch (Throwable $e) {
            Log::error('OpenAI prompt parser error.', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return $this->failedResult();
        }
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
