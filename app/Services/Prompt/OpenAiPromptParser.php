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
Your job is to infer the user's intent from everyday phrasing, including long, indirect, casual, typo-filled, or chatty Indonesian messages.

You MUST respond with valid JSON only. No explanation, no markdown, no code block.

JSON format:
{
  "intent": "CREATE" | "READ" | "UPDATE" | "DELETE",
  "confidence_score": 0.0 to 1.0,
  "parse_status": "parsed" | "ambiguous" | "unsupported" | "failed",
  "requires_confirmation": true | false,
  "entities": {
    "action": "LIST_EVENTS|RECAP|COUNT_EVENTS|SEARCH_EVENTS|CHECK_CONFLICTS|CHECK_AVAILABILITY|FIND_FREE_SLOT|CREATE_EVENTS|UPDATE_EVENTS|RESCHEDULE_EVENTS|DELETE_EVENTS|SET_REMINDER|UPDATE_REMINDER|DELETE_REMINDER",
    "human_response": "natural Indonesian reply after successful execution",
    "from": "YYYY-MM-DD or null",
    "to": "YYYY-MM-DD or null",
    "target_event_ids": ["UUID"] or null,
    "changes": {} or null,
    "entity_type": "event",
    "title": "string or null",
    "human_response": "string or null",
    "target_event_id": "UUID or null",
    "scheduled_date": "YYYY-MM-DD or null",
    "scheduled_dates": ["YYYY-MM-DD"] or null,
    "scheduled_time": "HH:MM:SS or null",
    "all_day": true | false,
    "recurrence": {
      "type": "daily" | "weekly" | "monthly" | null,
      "day_of_week": "monday" | "tuesday" | "wednesday" | "thursday" | "friday" | "saturday" | "sunday" | null,
      "interval": 1
    } | null,
    "description": "string or null",
    "search_query": "string or null",
    "reminder_minutes_before": "integer or null",
    "reminder_channel": "whatsapp" | "app" | "both" | null
  }
}

Rules:
- TODAY is provided. Resolve all relative dates and ranges, including today/tomorrow, next/previous week/month, weekdays, weekends, working days, explicit ranges, and "1 minggu ke depan". Put resolved boundaries in `from` and `to`.
- Choose one `action` from schema. For mutations, include only event UUIDs selected from `Agenda result` in `target_event_ids`; for new events include every resolved date in `scheduled_dates`; for edits include changed fields only in `changes`.
- Always produce a concise, natural Indonesian `human_response` describing completed action or answer.
- Conversation context may precede current message. For follow-ups such as "hapus no 1", "yang ini", or "yang tadi", choose the matching UUID from the prior `Agenda result` and return it as `target_event_id`. Parse only CURRENT USER MESSAGE as action; context is reference only.
- Infer the most likely task/calendar intent even if the user does not speak in command format.
- READ includes asking what schedule exists on a date or range, whether a day is free, when a task happens, or what agenda exists. Preserve range phrases such as "satu minggu terakhir" in search_query; do not collapse them into one date.
- UPDATE includes moving/rescheduling/renaming an existing task even when the user says things like "geser", "pindahin", "ganti", or "yang gym itu ubah ke pagi".
- DELETE includes canceling/removing a task even when the user says things like "hapus", "bat", "batal", "batalkan", or "yang meeting itu ga jadi".
- If intent is READ, set search_query to capture the user's filter criteria in natural language. For factual follow-up questions answered by conversation context, such as "itu bulan apa?", put the direct answer in `human_response` and do not query events.
- If command is unclear or ambiguous but still task/calendar related, prefer `parse_status: "ambiguous"` instead of `unsupported`.
- Use `unsupported` only when the request is clearly outside task/calendar assistant scope.
- Forwarded announcements, invitations, posters, screenshots, or meeting details are not an instruction to save immediately. Extract title, date, time, location, and agenda into description, choose `entity_type: "event"`, set `intent: "CREATE"`, and set `requires_confirmation: true`.
- This product manages agenda events only. Always use `entity_type: "event"`; do not create tasks.
- For "selama satu minggu", "seminggu", or "7 hari ke depan", put all seven resolved ISO dates in `scheduled_dates`.
- For any explicit date range, put every date in `scheduled_dates`. Example: "buat jadwal dari tanggal 1 sampai 4 Juli" has `scheduled_dates: ["YYYY-07-01", "YYYY-07-02", "YYYY-07-03", "YYYY-07-04"]`.
- For DELETE requests naming multiple dates, put every resolved ISO date in `scheduled_dates`. Example: "hapus jadwal tanggal 16 dan 17 Juli" has `scheduled_dates: ["YYYY-07-16", "YYYY-07-17"]`.
- If an image is attached, analyze the image for any schedule, task, or calendar information and extract it.
- If a voice transcription is provided, parse the transcription as the user's command.
- Parse reminder phrases such as "ingatkan 30 menit sebelumnya", "reminder 1 jam sebelum", or "ingatkan lewat app" into reminder_minutes_before and reminder_channel. Default reminder_channel to whatsapp when a reminder is requested without a channel.
PROMPT;

    private const RESCUE_SYSTEM_PROMPT = <<<'PROMPT'
You are doing a second-pass rescue parse for a WhatsApp task/calendar assistant.

The first parser said the message was unsupported or failed, but the message may still contain a valid task/calendar meaning. Be generous in interpretation while staying inside task/calendar scope.

Return valid JSON only using the same schema as before.

Important rules:
- If the user is asking about schedule, agenda, date, time, reminders, tasks, meetings, gym, plans, or calendar, do NOT return unsupported.
- Prefer `READ` for questions about what exists on a date or time.
- Prefer `CREATE` when the user is asking to add/schedule something.
- Prefer `UPDATE` when the user wants to move/change an existing task.
- Prefer `DELETE` when the user wants to cancel/remove an existing task.
- If you still cannot safely infer enough details, return `ambiguous` with `requires_confirmation: true`.
- Only return `unsupported` if the message is clearly unrelated to tasks, schedules, reminders, meetings, plans, or calendar usage.
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
                Log::warning('Prompt parser returned invalid JSON; retrying once.', [
                    'model' => $model,
                    'raw' => $content,
                    'user_id' => $userId,
                ]);

                return $this->retryWithRescuePrompt($text, $userId, $today, $model, $userContent);
            }

            if ($this->shouldRetryWithRescuePrompt($text, $parsed)) {
                return $this->retryWithRescuePrompt($text, $userId, $today, $model, $userContent);
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
     * @param  array<int, array{type: string, url?: string, data_url?: string, mime_type?: string}>|null  $attachments
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
            if ($attachment['type'] === 'image' && (isset($attachment['data_url']) || isset($attachment['url']))) {
                $parts[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $attachment['data_url'] ?? $attachment['url'],
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

    private function shouldRetryWithRescuePrompt(string $text, array $parsed): bool
    {
        $status = $parsed['parse_status'] ?? 'failed';

        if (! in_array($status, ['failed', 'unsupported'], true)) {
            return false;
        }

        return preg_match('/\b(jadwal|agenda|task|tugas|meeting|meet|gym|kalender|calendar|besok|hari ini|tanggal|jam|hapus|ubah|ganti|pindah|geser|buat|catat|ingatkan)\b/i', $text) === 1;
    }

    /**
     * @param  string|array<int, array<string, mixed>>  $userContent
     */
    private function retryWithRescuePrompt(string $text, string $userId, string $today, string $model, string|array $userContent): array
    {
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
                            'content' => self::RESCUE_SYSTEM_PROMPT."\n\nTODAY = {$today}",
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
                Log::error('Prompt parser rescue API failed.', [
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
                Log::warning('Prompt parser rescue returned invalid JSON.', [
                    'model' => $model,
                    'raw' => $content,
                    'user_id' => $userId,
                ]);

                return $this->failedResult();
            }

            return $parsed;
        } catch (Throwable $e) {
            Log::error('Prompt parser rescue error.', [
                'model' => $model,
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
