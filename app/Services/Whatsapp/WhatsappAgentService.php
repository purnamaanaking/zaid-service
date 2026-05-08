<?php

namespace App\Services\Whatsapp;

use App\Models\PromptAction;
use App\Models\PromptRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\Agenda\AgendaQueryService;
use App\Services\Tasks\TaskMutationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsappAgentService
{
    private const MAX_HISTORY_MESSAGES = 10;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu adalah Zaid, asisten pribadi via WhatsApp. Kamu ngobrol pakai bahasa Indonesia santai, kayak temen deket.

KEMAMPUAN:
- Lihat/cek jadwal & task di tanggal tertentu
- Buat task/jadwal baru
- Ubah task yang sudah ada (judul, tanggal, jam)
- Hapus/batalkan task (satu atau banyak sekaligus)
- Jawab greeting & obrolan ringan soal jadwal

ATURAN PENTING:
1. Balas singkat, santai, kayak chat temen. Jangan formal/kaku.
2. LANGSUNG EKSEKUSI. Jangan tanya deskripsi, lokasi, atau detail tambahan kecuali user sendiri yang mau tambahin. Kalau user bilang "buat meeting jam 3", langsung buat dengan title "Meeting" dan jam 15:00. Selesai.
3. Kalau user mau ubah/hapus dan ada task yang cocok di DATA TASK, WAJIB sertakan task_id dari data. Jangan pernah return action tanpa task_id kalau ada match.
4. Kalau user bilang "hapus semua" atau "hapus semuanya", hapus satu-satu dengan multiple action. Tapi karena kamu cuma bisa 1 action per response, hapus yang pertama dulu dan bilang "Aku hapus [nama] dulu ya, kirim 'lanjut' buat hapus sisanya."
5. Kalau user mau ubah/hapus tapi ada beberapa task mirip, kasih list bernomor dan tanya yang mana.
6. Kalau cuma greeting ("bro", "halo", "hey"), bales santai dan tanya ada yang bisa dibantu.
7. Kalau user ngasih koreksi/info tambahan (misal "hari ini tanggal 8"), terima dan gunakan info itu.
8. Format jam pakai HH:MM (24h), tanggal pakai format natural Indonesia.
9. Jangan pernah bilang "aku belum bisa bantu". Kalau ga ngerti, tanya balik.
10. Bisa handle typo dan bahasa casual/slang Indonesia.
11. Kalau user jawab singkat setelah kamu kasih opsi ("yang pertama", "nomor 2", "iya"), pahami itu sebagai jawaban dari pertanyaanmu sebelumnya di chat history.

FORMAT RESPONSE (JSON only, no markdown, no code block):
{
  "reply": "teks balasan ke user",
  "action": null | {
    "type": "create" | "read" | "update" | "delete",
    "task_id": "uuid dari DATA TASK or null untuk create",
    "data": {
      "title": "string or null",
      "scheduled_date": "YYYY-MM-DD or null",
      "scheduled_time": "HH:MM:SS or null",
      "description": "string or null",
      "all_day": false
    }
  }
}

CONTOH INTERAKSI:
User: "bro"
→ {"reply": "Yo, ada apa nih? Mau cek jadwal atau ada yang perlu diatur?", "action": null}

User: "tambah meeting jam 3 sore"
→ {"reply": "Siap, meeting jam 15:00 udah aku catat! 👍", "action": {"type": "create", "task_id": null, "data": {"title": "Meeting", "scheduled_date": "2026-05-08", "scheduled_time": "15:00:00"}}}
(JANGAN tanya deskripsi/lokasi, langsung buat!)

User: "ubah meeting jadi jam 10 malam"
(ada task [abc-123] Meeting @ 15:00 di DATA TASK)
→ {"reply": "Done, Meeting udah aku pindah ke jam 22:00! 👍", "action": {"type": "update", "task_id": "abc-123", "data": {"scheduled_time": "22:00:00"}}}

User: "hapus meeting"
(ada task [abc-123] Meeting @ 22:00 di DATA TASK)
→ {"reply": "Oke, Meeting udah aku hapus! 🗑️", "action": {"type": "delete", "task_id": "abc-123", "data": {}}}

User: "hapus semua jadwal hari ini"
(ada task [abc-123] Meeting @ 15:00 dan [def-456] Gym @ 20:30)
→ {"reply": "Aku hapus Meeting dulu ya. Kirim 'lanjut' buat hapus Gym juga.", "action": {"type": "delete", "task_id": "abc-123", "data": {}}}
PROMPT;

    public function __construct(
        private readonly TaskMutationService $taskMutationService,
        private readonly AgendaQueryService $agendaQueryService,
    ) {}

    /**
     * @param  array<int, array{type: string, url?: string, data_url?: string, mime_type?: string, text?: string}>|null  $attachments
     * @return array{prompt_request_id: string, human_response: string}
     */
    public function handle(User $user, string $text, string $channel = 'whatsapp', ?array $attachments = null): array
    {
        $today = now()->format('Y-m-d');
        $dayName = now()->locale('id')->isoFormat('dddd');
        $now = now()->format('H:i');

        $history = $this->getConversationHistory($user);
        $tasks = $this->getUserTaskContext($user, $today);

        $messages = $this->buildMessages($today, $dayName, $now, $tasks, $history, $text, $attachments);

        $promptRequest = PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'raw_text' => $text,
            'normalized_text' => $text,
            'intent' => null,
            'confidence_score' => 0,
            'parse_status' => 'pending',
            'extracted_entities' => null,
            'execution_status' => 'pending',
        ]);

        try {
            $aiResponse = $this->callOpenAi($messages, $user->id, ! empty($attachments));
        } catch (Throwable $e) {
            Log::error('WhatsApp agent AI call failed.', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            $promptRequest->update([
                'parse_status' => 'failed',
                'execution_status' => 'failed',
            ]);

            return [
                'prompt_request_id' => $promptRequest->id,
                'human_response' => 'Waduh, lagi ada gangguan nih. Coba lagi bentar ya! 🙏',
            ];
        }

        $reply = $aiResponse['reply'] ?? 'Hmm, aku kurang nangkep. Coba ulangin dong?';
        $action = $aiResponse['action'] ?? null;

        $intent = $action['type'] ?? null;
        $promptRequest->update([
            'intent' => $intent ? strtoupper($intent) : null,
            'parse_status' => 'parsed',
            'extracted_entities' => $action['data'] ?? null,
        ]);

        if ($action !== null) {
            $result = $this->executeAction($promptRequest, $user, $action, $channel, $today);

            if ($result !== null) {
                $reply = $result['reply'] ?? $reply;

                $promptRequest->update([
                    'execution_status' => 'executed',
                    'execution_summary' => $result,
                ]);
            } else {
                $promptRequest->update(['execution_status' => 'executed']);
            }
        } else {
            $promptRequest->update(['execution_status' => 'executed']);
        }

        return [
            'prompt_request_id' => $promptRequest->id,
            'human_response' => $reply,
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function getConversationHistory(User $user): array
    {
        $messages = WhatsappMessage::query()
            ->where('user_id', $user->id)
            ->whereNotNull('message_text')
            ->where('message_text', '!=', '')
            ->orderByDesc('created_at')
            ->limit(self::MAX_HISTORY_MESSAGES)
            ->get(['direction', 'message_text', 'created_at'])
            ->reverse()
            ->values();

        return $messages->map(fn (WhatsappMessage $m) => [
            'role' => $m->direction === 'inbound' ? 'user' : 'assistant',
            'content' => $m->message_text,
        ])->all();
    }

    /**
     * @return string
     */
    private function getUserTaskContext(User $user, string $today): string
    {
        $todayTasks = $this->agendaQueryService->dayAgenda($user, $today);
        $tomorrowTasks = $this->agendaQueryService->dayAgenda($user, Carbon::parse($today)->addDay()->format('Y-m-d'));

        $lines = [];

        if ($todayTasks->isNotEmpty()) {
            $lines[] = "TASK HARI INI ({$today}):";
            foreach ($todayTasks as $task) {
                $time = $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : 'all-day';
                $lines[] = "- [{$task->id}] {$task->title} @ {$time} (status: {$task->status})";
            }
        } else {
            $lines[] = "TASK HARI INI ({$today}): kosong";
        }

        $tomorrow = Carbon::parse($today)->addDay()->format('Y-m-d');
        if ($tomorrowTasks->isNotEmpty()) {
            $lines[] = "\nTASK BESOK ({$tomorrow}):";
            foreach ($tomorrowTasks as $task) {
                $time = $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : 'all-day';
                $lines[] = "- [{$task->id}] {$task->title} @ {$time} (status: {$task->status})";
            }
        }

        // Also include recent tasks from the last 7 days for update/delete context
        $recentTasks = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('scheduled_date', '>=', Carbon::parse($today)->subDays(3)->format('Y-m-d'))
            ->where('scheduled_date', '<=', Carbon::parse($today)->addDays(7)->format('Y-m-d'))
            ->whereNotIn('id', $todayTasks->pluck('id')->merge($tomorrowTasks->pluck('id'))->all())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(15)
            ->get();

        if ($recentTasks->isNotEmpty()) {
            $lines[] = "\nTASK MINGGU INI (lainnya):";
            foreach ($recentTasks as $task) {
                $date = $task->scheduled_date?->format('Y-m-d') ?? '?';
                $time = $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : 'all-day';
                $lines[] = "- [{$task->id}] {$task->title} @ {$date} {$time} (status: {$task->status})";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  array<int, array{type: string, url?: string, data_url?: string, mime_type?: string}>|null  $attachments
     * @return array<int, array{role: string, content: mixed}>
     */
    private function buildMessages(string $today, string $dayName, string $now, string $tasks, array $history, string $text, ?array $attachments): array
    {
        $systemContent = self::SYSTEM_PROMPT
            . "\n\nKONTEKS SAAT INI:"
            . "\n- Hari: {$dayName}"
            . "\n- Tanggal: {$today}"
            . "\n- Jam: {$now}"
            . "\n\nDATA TASK USER:\n{$tasks}";

        $messages = [
            ['role' => 'system', 'content' => $systemContent],
        ];

        // Add conversation history (minus the current message since we add it separately)
        foreach ($history as $msg) {
            $messages[] = $msg;
        }

        // Build user content (text + optional images)
        $userContent = $text;
        if (! empty($attachments)) {
            $parts = [['type' => 'text', 'text' => $text]];
            foreach ($attachments as $att) {
                if ($att['type'] === 'image' && (isset($att['data_url']) || isset($att['url']))) {
                    $parts[] = [
                        'type' => 'image_url',
                        'image_url' => ['url' => $att['data_url'] ?? $att['url']],
                    ];
                }
            }
            $userContent = $parts;
        }

        $messages[] = ['role' => 'user', 'content' => $userContent];

        return $messages;
    }

    /**
     * @param  array<int, array{role: string, content: mixed}>  $messages
     * @return array{reply: string, action: array|null}
     */
    private function callOpenAi(array $messages, string $userId, bool $hasMedia): array
    {
        $model = $hasMedia
            ? config('services.openai.model_multimodal', 'gemini/gemini-2.0-flash')
            : config('services.openai.model_text', 'MiniMax-M2.7-highspeed');
        $apiKey = config('services.openai.api_key', '');
        $apiBase = config('services.openai.api_base', 'https://api.openai.com/v1');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post("{$apiBase}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 1024,
                'temperature' => 0.3,
            ]);

        if (! $response->successful()) {
            Log::error('WhatsApp agent API failed.', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body(),
                'user_id' => $userId,
            ]);

            throw new \RuntimeException('OpenAI API returned ' . $response->status());
        }

        $content = $response->json('choices.0.message.content', '{}');
        $content = preg_replace('/^```(?:json)?\s*/', '', trim($content));
        $content = preg_replace('/\s*```$/', '', $content);

        $parsed = json_decode($content, true);

        if (! is_array($parsed) || ! isset($parsed['reply'])) {
            Log::warning('WhatsApp agent returned non-JSON, using raw text.', [
                'raw' => $content,
                'user_id' => $userId,
            ]);

            // If AI responded with plain text, use it directly
            return [
                'reply' => is_string($content) && strlen($content) > 0 ? $content : 'Hmm, coba ulangin dong?',
                'action' => null,
            ];
        }

        return $parsed;
    }

    /**
     * @param  array{type: string, task_id: string|null, data: array<string, mixed>}  $action
     * @return array<string, mixed>|null
     */
    private function executeAction(PromptRequest $promptRequest, User $user, array $action, string $channel, string $today): ?array
    {
        $type = $action['type'] ?? null;
        $data = $action['data'] ?? [];
        $taskId = $action['task_id'] ?? null;

        return match ($type) {
            'create' => $this->executeCreate($promptRequest, $user, $data, $channel),
            'read' => $this->executeRead($user, $data, $today),
            'update' => $this->executeUpdate($promptRequest, $user, $taskId, $data, $channel),
            'delete' => $this->executeDelete($promptRequest, $user, $taskId, $channel),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function executeCreate(PromptRequest $promptRequest, User $user, array $data, string $channel): array
    {
        $task = $this->taskMutationService->create($user, [
            'title' => $data['title'] ?? 'Task baru',
            'description' => $data['description'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'all_day' => $data['all_day'] ?? false,
        ], $channel, $promptRequest->id);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'create',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => $data,
            'result_payload' => ['task_id' => $task->id],
        ]);

        return ['action' => 'create_task', 'task_id' => $task->id];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function executeRead(User $user, array $data, string $today): array
    {
        $date = $data['scheduled_date'] ?? $today;
        $items = $this->agendaQueryService->dayAgenda($user, $date);

        return [
            'action' => 'read_agenda',
            'date' => $date,
            'count' => $items->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function executeUpdate(PromptRequest $promptRequest, User $user, ?string $taskId, array $data, string $channel): array
    {
        if ($taskId === null) {
            return ['action' => 'update_task', 'error' => 'no_task_id'];
        }

        $task = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->find($taskId);

        if ($task === null) {
            return ['action' => 'update_task', 'error' => 'task_not_found'];
        }

        $this->taskMutationService->update($task, $user, $data, $channel, $promptRequest->id);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'update',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => $data,
            'result_payload' => ['task_id' => $task->id],
        ]);

        return ['action' => 'update_task', 'task_id' => $task->id];
    }

    /**
     * @return array<string, mixed>
     */
    private function executeDelete(PromptRequest $promptRequest, User $user, ?string $taskId, string $channel): array
    {
        if ($taskId === null) {
            return ['action' => 'delete_task', 'error' => 'no_task_id'];
        }

        $task = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->find($taskId);

        if ($task === null) {
            return ['action' => 'delete_task', 'error' => 'task_not_found'];
        }

        $title = $task->title;
        $this->taskMutationService->delete($task, $user, $channel, $promptRequest->id);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'delete',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => ['title' => $title],
            'result_payload' => ['task_id' => $task->id],
        ]);

        return ['action' => 'delete_task', 'task_id' => $task->id];
    }
}
