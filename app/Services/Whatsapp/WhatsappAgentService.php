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
- Lihat task berdasarkan status: yang belum dikerjain (pending), yang udah selesai (completed)
- Buat task/jadwal baru
- Ubah task yang sudah ada (judul, tanggal, jam)
- Hapus/batalkan task (satu atau banyak sekaligus)
- Tandai task selesai / complete
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
12. JANGAN PERNAH tampilkan UUID/task_id ke user di reply. ID itu cuma internal buat action JSON. Di reply, cukup sebut nama task dan jamnya.
13. Kalau READ jadwal, tulis rapi dan natural. Contoh: "Hari ini kamu ada:\n1. Meeting - 13:00\n2. Gym - 20:30"
14. PAHAMI bahasa gaul/singkatan Indonesia: "blom" = belum, "kerjain" = kerjakan, "mo" = mau, "nnt" = nanti, "gw/gue/gua" = aku, "lu/lo" = kamu, "klo" = kalau, "yg" = yang, "udh" = sudah, "bsk" = besok, "jgn" = jangan, "gmn" = gimana, "btw" = by the way, "otw" = on the way, "gpp" = ga papa.
15. Kalau user tanya task yang belum dikerjain / pending / belum selesai, tampilkan semua task dengan status 'pending' dari DATA TASK.
16. Kalau user mau tandain task selesai, gunakan action type 'complete' dengan task_id.

FORMAT RESPONSE (JSON only, no markdown, no code block):
{
  "reply": "teks balasan ke user",
  "action": null | {
    "type": "create" | "read" | "update" | "delete" | "complete",
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

User: "list jadwal hari ini" atau "cek jadwal" atau "jadwal apa hari ini"
(ada task [abc-123] Meeting @ 13:00 dan [def-456] Gym @ 20:30 di DATA TASK)
→ {"reply": "Hari ini kamu ada:\n1. Meeting - 13:00\n2. Gym - 20:30\n\nMau ubah atau tambah sesuatu?", "action": {"type": "read", "task_id": null, "data": {"scheduled_date": "2026-05-08"}}}
(JANGAN tampilkan UUID di reply! Cukup nama + jam)

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

User: "task apa aja yg blom ku kerjain" atau "yg pending apa" atau "belum selesai apa"
(ada task pending di DATA TASK)
→ {"reply": "Task yang belum kelar:\n1. Meeting - hari ini 13:00\n2. Gym - hari ini 20:30\n\nMau selesaiin yang mana?", "action": {"type": "read", "task_id": null, "data": {}}}

User: "udah selesai meeting" atau "meeting udah beres"
(ada task [abc-123] Meeting di DATA TASK)
→ {"reply": "Nice, Meeting udah aku tandain selesai! ✅", "action": {"type": "complete", "task_id": "abc-123", "data": {}}}
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
        $normalizedText = $this->normalizeIncomingText($text);

        $history = $this->getConversationHistory($user);
        $tasks = $this->getUserTaskContext($user, $today);

        $promptRequest = PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'raw_text' => $text,
            'normalized_text' => $normalizedText,
            'intent' => null,
            'confidence_score' => 0,
            'parse_status' => 'pending',
            'extracted_entities' => null,
            'execution_status' => 'pending',
        ]);

        if ($quickDelete = $this->handleQuickDeleteQueries($promptRequest, $user, $normalizedText, $channel)) {
            return $quickDelete;
        }

        if ($quickReply = $this->handleQuickReadQueries($user, $normalizedText, $today)) {
            $promptRequest->update([
                'intent' => 'READ',
                'parse_status' => 'parsed',
                'execution_status' => 'executed',
                'execution_summary' => ['action' => 'read_quick'],
            ]);

            return [
                'prompt_request_id' => $promptRequest->id,
                'human_response' => $quickReply,
            ];
        }

        $messages = $this->buildMessages($today, $dayName, $now, $tasks, $history, $normalizedText, $attachments);

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

        $formatTask = function (Task $task, bool $showDate = false): string {
            $type = $task->scheduled_time ? 'jadwal' : 'task';
            $time = $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : 'tanpa jam';
            $date = $showDate && $task->scheduled_date ? $task->scheduled_date->format('Y-m-d') . ' ' : '';
            return "- [{$task->id}] [{$type}] {$task->title} @ {$date}{$time} (status: {$task->status})";
        };

        if ($todayTasks->isNotEmpty()) {
            $lines[] = "TASK & JADWAL HARI INI ({$today}):";
            foreach ($todayTasks as $task) {
                $lines[] = $formatTask($task);
            }
        } else {
            $lines[] = "TASK & JADWAL HARI INI ({$today}): kosong";
        }

        $tomorrow = Carbon::parse($today)->addDay()->format('Y-m-d');
        if ($tomorrowTasks->isNotEmpty()) {
            $lines[] = "\nTASK & JADWAL BESOK ({$tomorrow}):";
            foreach ($tomorrowTasks as $task) {
                $lines[] = $formatTask($task);
            }
        }

        // To-do tasks without date (pending)
        $undatedTasks = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNull('scheduled_date')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($undatedTasks->isNotEmpty()) {
            $lines[] = "\nTASK TANPA TANGGAL (to-do):";
            foreach ($undatedTasks as $task) {
                $lines[] = "- [{$task->id}] [task] {$task->title} (status: {$task->status})";
            }
        }

        // Recent tasks from nearby days
        $excludeIds = $todayTasks->pluck('id')->merge($tomorrowTasks->pluck('id'))->merge($undatedTasks->pluck('id'))->all();
        $recentTasks = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', Carbon::parse($today)->subDays(3)->format('Y-m-d'))
            ->where('scheduled_date', '<=', Carbon::parse($today)->addDays(7)->format('Y-m-d'))
            ->whereNotIn('id', $excludeIds)
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(15)
            ->get();

        if ($recentTasks->isNotEmpty()) {
            $lines[] = "\nTASK & JADWAL MINGGU INI (lainnya):";
            foreach ($recentTasks as $task) {
                $lines[] = $formatTask($task, true);
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

    private function normalizeIncomingText(string $text): string
    {
        $normalized = strtolower(trim($text));
        $normalized = preg_replace('/\b(halo|hai|hi|bro|bos|sis|bang|kak|woy|woi|oi|permisi)\b/u', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', trim((string) $normalized));

        return $normalized;
    }

    /**
     * @return array{prompt_request_id: string, human_response: string}|null
     */
    private function handleQuickDeleteQueries(PromptRequest $promptRequest, User $user, string $text, string $channel): ?array
    {
        if ($text === '') {
            return null;
        }

        $asksDelete = preg_match('/\b(hapus|delete|buang|remove)\b/u', $text) === 1;
        $asksTasks = preg_match('/\b(task|tasks|tugas|todo|to-do)\b/u', $text) === 1;
        $asksSchedule = preg_match('/\b(jadwal|agenda|calendar|kalender)\b/u', $text) === 1;
        $asksPending = preg_match('/\b(belum|blom|pending|belum selesai|belom selesai|belum kelar|blom kelar|belom kelar)\b/u', $text) === 1;

        if (! $asksDelete || ! $asksTasks || $asksSchedule || ! $asksPending) {
            return null;
        }

        $task = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('status', 'pending')
            ->whereNull('scheduled_time')
            ->orderByRaw('CASE WHEN scheduled_date IS NULL THEN 0 ELSE 1 END')
            ->orderBy('scheduled_date')
            ->first();

        if (! $task) {
            $promptRequest->update([
                'intent' => 'DELETE',
                'parse_status' => 'parsed',
                'execution_status' => 'executed',
                'execution_summary' => ['action' => 'delete_quick_none'],
            ]);

            return [
                'prompt_request_id' => $promptRequest->id,
                'human_response' => 'Task pending yang bisa dihapus lagi kosong bro 👌',
            ];
        }

        $title = $task->title;
        $this->taskMutationService->delete($task, $user, $channel, $promptRequest->id);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'delete',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => ['title' => $title, 'mode' => 'quick_delete_pending_tasks'],
            'result_payload' => ['task_id' => $task->id],
        ]);

        $promptRequest->update([
            'intent' => 'DELETE',
            'parse_status' => 'parsed',
            'execution_status' => 'executed',
            'execution_summary' => ['action' => 'delete_task', 'task_id' => $task->id],
        ]);

        return [
            'prompt_request_id' => $promptRequest->id,
            'human_response' => "Siap, task \"{$title}\" udah aku hapus.",
        ];
    }

    private function handleQuickReadQueries(User $user, string $text, string $today): ?string
    {
        if ($text === '') {
            return null;
        }

        $asksTasks = preg_match('/\b(task|tasks|tugas|todo|to-do)\b/u', $text) === 1;
        $asksSchedule = preg_match('/\b(jadwal|agenda|calendar|kalender)\b/u', $text) === 1;
        $asksPending = preg_match('/\b(belum|blom|pending|belum selesai|belom selesai|belum kelar|blom kelar|belom kelar)\b/u', $text) === 1;
        $asksOverdue = preg_match('/\b(overdue|telat|kelewat|lewat deadline|jatuh tempo)\b/u', $text) === 1;
        $asksToday = preg_match('/\b(hari ini|hr ini|today|sekarang)\b/u', $text) === 1;
        $asksMine = preg_match('/\b(gua|gue|gw|aku|saya|ku)\b/u', $text) === 1;
        $asksList = preg_match('/\b(cek|lihat|list|apa|apa aja|apa saja|mana|yang)\b/u', $text) === 1;

        if (! ($asksTasks || $asksSchedule || $asksOverdue) || ! ($asksList || $asksMine || $asksPending || $asksToday || $asksOverdue)) {
            return null;
        }

        if ($asksOverdue) {
            $items = Task::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->where('status', 'pending')
                ->whereNotNull('scheduled_date')
                ->whereDate('scheduled_date', '<', $today)
                ->when($asksTasks && ! $asksSchedule, fn ($query) => $query->whereNull('scheduled_time'))
                ->when($asksSchedule && ! $asksTasks, fn ($query) => $query->whereNotNull('scheduled_time'))
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(10)
                ->get();

            if ($items->isEmpty()) {
                return 'Overdue kamu aman bro, lagi ga ada yang kelewat ✅';
            }

            $lines = $items->values()->map(function (Task $task, int $index) {
                $suffix = $task->scheduled_time
                    ? ' - '.$task->scheduled_date->format('Y-m-d').' '.substr((string) $task->scheduled_time, 0, 5)
                    : ' - '.$task->scheduled_date->format('Y-m-d');

                return ($index + 1).'. '.$task->title.$suffix;
            })->implode("\n");

            return "Yang overdue:\n{$lines}";
        }

        if ($asksToday && ($asksTasks || $asksSchedule)) {
            $items = Task::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->whereDate('scheduled_date', $today)
                ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
                ->orderBy('scheduled_time')
                ->limit(10)
                ->get();

            if ($items->isEmpty()) {
                return $asksTasks
                    ? 'Task kamu buat hari ini kosong bro 👌'
                    : 'Hari ini jadwal kamu kosong bro 👌';
            }

            $lines = $items->values()->map(function (Task $task, int $index) {
                $suffix = $task->scheduled_time
                    ? ' - '.substr((string) $task->scheduled_time, 0, 5)
                    : ' - tanpa jam';

                return ($index + 1).'. '.$task->title.$suffix;
            })->implode("\n");

            return $asksTasks
                ? "Task kamu buat hari ini:\n{$lines}"
                : "Jadwal kamu hari ini:\n{$lines}";
        }

        if ($asksPending || $asksTasks) {
            $pendingTasks = Task::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->where('status', 'pending')
                ->when($asksTasks && ! $asksSchedule, fn ($query) => $query->whereNull('scheduled_time'))
                ->when($asksSchedule && ! $asksTasks, fn ($query) => $query->whereNotNull('scheduled_time'))
                ->orderByRaw('CASE WHEN scheduled_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->limit(10)
                ->get();

            if ($pendingTasks->isEmpty()) {
                return 'Task pending kamu lagi kosong bro ✅';
            }

            $lines = $pendingTasks->values()->map(function (Task $task, int $index) {
                $when = '';
                if ($task->scheduled_date && $task->scheduled_time) {
                    $when = ' - '.$task->scheduled_date->format('Y-m-d').' '.substr((string) $task->scheduled_time, 0, 5);
                } elseif ($task->scheduled_date) {
                    $when = ' - '.$task->scheduled_date->format('Y-m-d');
                }

                return ($index + 1).'. '.$task->title.$when;
            })->implode("\n");

            return "Task kamu yang belum kelar:\n{$lines}";
        }

        if ($asksSchedule || $asksToday) {
            $items = $this->agendaQueryService->dayAgenda($user, $today);

            if ($items->isEmpty()) {
                return 'Hari ini jadwal kamu kosong bro 👌';
            }

            $lines = $items->values()->map(function (Task $task, int $index) {
                $time = $task->scheduled_time ? substr((string) $task->scheduled_time, 0, 5) : 'tanpa jam';
                return ($index + 1).'. '.$task->title.' - '.$time;
            })->implode("\n");

            return "Jadwal kamu hari ini:\n{$lines}";
        }

        return null;
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
            'complete' => $this->executeComplete($promptRequest, $user, $taskId, $channel),
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

    /**
     * @return array<string, mixed>
     */
    private function executeComplete(PromptRequest $promptRequest, User $user, ?string $taskId, string $channel): array
    {
        if ($taskId === null) {
            return ['action' => 'complete_task', 'error' => 'no_task_id'];
        }

        $task = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->find($taskId);

        if ($task === null) {
            return ['action' => 'complete_task', 'error' => 'task_not_found'];
        }

        $this->taskMutationService->complete($task, $user, $channel);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'complete',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => ['title' => $task->title],
            'result_payload' => ['task_id' => $task->id],
        ]);

        return ['action' => 'complete_task', 'task_id' => $task->id];
    }
}
