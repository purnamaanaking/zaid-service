<?php

namespace App\Services\Whatsapp;

use App\Models\CalendarEvent;
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
- Baca poster/screenshot/pengumuman event, ekstrak detailnya, lalu minta konfirmasi sebelum masuk kalender
- Ubah task yang sudah ada (judul, tanggal, jam)
- Hapus/batalkan task (satu atau banyak sekaligus)
- Tandai task selesai / complete
- Jawab greeting & obrolan ringan soal jadwal

ATURAN PENTING:
1. Balas singkat, santai, kayak chat temen. Jangan formal/kaku.
2. LANGSUNG EKSEKUSI. Jangan tanya deskripsi, lokasi, atau detail tambahan kecuali user sendiri yang mau tambahin.
3. Bedakan jenis data: `event` untuk meeting, acara, jadwal, kelas, janji, appointment, atau kalender yang punya waktu; `task` untuk tugas, todo, follow up, atau pekerjaan. Jika user menyebut jenisnya, WAJIB pakai jenis itu. Jika tidak jelas, gunakan `task`.
4. Kalau user mau ubah/hapus dan ada task yang cocok di DATA TASK, WAJIB sertakan task_id dari data. Jangan pernah return action tanpa task_id kalau ada match.
5. Kalau user bilang "hapus semua" atau "hapus semuanya", hapus satu-satu dengan multiple action. Tapi karena kamu cuma bisa 1 action per response, hapus yang pertama dulu dan bilang "Aku hapus [nama] dulu ya, kirim 'lanjut' buat hapus sisanya."
6. Kalau user mau ubah/hapus tapi ada beberapa task mirip, kasih list bernomor dan tanya yang mana.
7. Kalau cuma greeting ("bro", "halo", "hey"), bales santai dan tanya ada yang bisa dibantu.
8. Kalau user ngasih koreksi/info tambahan (misal "hari ini tanggal 8"), terima dan gunakan info itu.
9. Format jam pakai HH:MM:SS (24h), tanggal pakai YYYY-MM-DD di action. "jam 7 pagi" = 07:00:00, "jam 7 malam" = 19:00:00.
10. Jangan pernah bilang "aku belum bisa bantu". Kalau ga ngerti, tanya balik.
11. Bisa handle typo dan bahasa casual/slang Indonesia.
12. Kalau user jawab singkat setelah kamu kasih opsi ("yang pertama", "nomor 2", "iya"), pahami itu sebagai jawaban dari pertanyaanmu sebelumnya di chat history.
13. JANGAN PERNAH tampilkan UUID/task_id ke user di reply. ID itu cuma internal buat action JSON. Di reply, cukup sebut nama task dan jamnya.
14. Kalau READ jadwal, tulis rapi dan natural. Contoh: "Hari ini kamu ada:\n1. Meeting - 13:00\n2. Gym - 20:30"
15. PAHAMI bahasa gaul/singkatan Indonesia: "blom" = belum, "kerjain" = kerjakan, "mo" = mau, "nnt" = nanti, "gw/gue/gua" = aku, "lu/lo" = kamu, "klo" = kalau, "yg" = yang, "udh" = sudah, "bsk" = besok, "jgn" = jangan, "gmn" = gimana, "btw" = by the way, "otw" = on the way, "gpp" = ga papa.
16. Kalau user tanya task yang belum dikerjain / pending / belum selesai, tampilkan semua task dengan status 'pending' dari DATA TASK.
17. Kalau user mau tandain task selesai, gunakan action type 'complete' dengan task_id.
18. Kalau user mengirim gambar/poster/pengumuman/event, JANGAN langsung create. Ekstrak judul, tanggal, jam, lokasi, deskripsi lalu return action type 'confirm_create' dan tanya user mau ditambahkan ke kalender atau tidak.
19. Kalau user menjawab setuju setelah kamu menawarkan event/task sebelumnya (misal: "iya", "gas", "tambahkan", "boleh", "ok"), gunakan action type 'confirm' agar sistem mengeksekusi pending action terakhir.
20. Kalau user menolak (misal: "jangan", "ga usah", "batal"), gunakan action type 'cancel' agar pending action terakhir dibatalkan.

FORMAT RESPONSE (JSON only, no markdown, no code block):
{
  "reply": "teks balasan ke user",
  "action": null | {
    "type": "create" | "read" | "update" | "delete" | "complete" | "confirm_create" | "confirm" | "cancel",
    "task_id": "uuid dari DATA TASK or null untuk create",
    "data": {
      "entity_type": "task" | "event",
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
→ {"reply": "Siap, meeting jam 15:00 udah aku catat! 👍", "action": {"type": "create", "task_id": null, "data": {"entity_type": "event", "title": "Meeting", "scheduled_date": "2026-05-08", "scheduled_time": "15:00:00", "all_day": false}}}

User: "buat task follow up dosen besok"
→ {"reply": "Siap, follow up dosen besok udah aku buat!", "action": {"type": "create", "task_id": null, "data": {"entity_type": "task", "title": "Follow up dosen", "scheduled_date": "2026-05-09", "scheduled_time": null, "all_day": true}}}
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

User mengirim poster event cek gula darah gratis
→ {"reply": "Aku nemu event nih: Cek Gula Darah Gratis, Senin 8 Juni 2026 jam 08:00 di Ruang Harmony, Telkom University Lt. 3. Mau aku tambahin ke kalender?", "action": {"type": "confirm_create", "task_id": null, "data": {"title": "Cek Gula Darah Gratis", "scheduled_date": "2026-06-08", "scheduled_time": "08:00:00", "description": "Gratis cek gula darah. Lokasi: Ruang Harmony, Telkom University Lt. 3. Catatan: kuota 50 orang, sampai jajan habis.", "all_day": false}}}

User: "iya tambahin"
→ {"reply": "Siap, aku tambahin ke kalender ya 👍", "action": {"type": "confirm", "task_id": null, "data": {}}}
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

        if ($confirmation = $this->handlePendingConfirmation($promptRequest, $user, $normalizedText, $channel)) {
            return $confirmation;
        }

        if ($quickRead = $this->handleQuickReadQueries($user, $normalizedText, $today)) {
            $promptRequest->update([
                'intent' => 'READ',
                'parse_status' => 'parsed',
                'execution_status' => 'executed',
            ]);

            return [
                'prompt_request_id' => $promptRequest->id,
                'human_response' => $quickRead,
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

        $reply = $aiResponse['reply'] ?? '';
        $actions = $aiResponse['actions'] ?? [];
        $results = [];

        foreach (array_slice($actions, 0, 5) as $action) {
            if (! is_array($action)) {
                continue;
            }

            if (($action['type'] ?? null) === 'create') {
                $action['data']['entity_type'] = $this->creationEntityType($normalizedText, $action['data']['entity_type'] ?? null);
            }

            $result = $this->executeAction($promptRequest, $user, $action, $channel, $today);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        if ($reply === '') {
            $reply = $results === []
                ? 'Pesanmu masuk, tapi aku belum bisa baca perintahnya. Coba pisahkan perintah per baris ya bro.'
                : 'Siap, '.count($results).' item sudah aku proses.';
        }

        $intent = $actions[0]['type'] ?? null;
        $promptRequest->update([
            'intent' => $intent ? strtoupper($intent) : null,
            'parse_status' => $results === [] && $actions === [] ? 'failed' : 'parsed',
            'extracted_entities' => $actions[0]['data'] ?? null,
            'execution_status' => $results === [] && $actions === [] ? 'failed' : 'executed',
            'execution_summary' => $results,
        ]);

        Log::info('WhatsApp agent processed.', [
            'prompt_request_id' => $promptRequest->id,
            'action_count' => count($actions),
            'successful_action_count' => count($results),
        ]);

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
     * @return array{reply: string, actions: array<int, array<string, mixed>>}
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
                'reply' => is_string($content) ? $content : '',
                'actions' => [],
            ];
        }

        if (! isset($parsed['actions'])) {
            $parsed['actions'] = isset($parsed['action']) && is_array($parsed['action'])
                ? [$parsed['action']]
                : [];
        }

        return $parsed;
    }

    private function creationEntityType(string $text, mixed $aiChoice): string
    {
        $isTask = preg_match('/\b(task|tugas|todo|to-do|follow\s*up|kerjakan)\b/u', $text) === 1;
        $isEvent = preg_match('/\b(meeting|acara|event|jadwal|kelas|janji|appointment|kalender)\b/u', $text) === 1;

        if ($isTask xor $isEvent) {
            return $isEvent ? 'event' : 'task';
        }

        return $aiChoice === 'event' ? 'event' : 'task';
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
    private function handlePendingConfirmation(PromptRequest $promptRequest, User $user, string $text, string $channel): ?array
    {
        $pending = $this->latestPendingConfirmation($user);

        if (! $pending) {
            return null;
        }

        $isConfirm = preg_match('/\b(iya|ya|yes|y|gas|gass|ok|oke|boleh|tambahkan|tambahin|add|sip|siap|lanjut)\b/u', $text) === 1;
        $isCancel = preg_match('/\b(jangan|ga usah|gak usah|ngga|nggak|tidak|batal|cancel|skip)\b/u', $text) === 1;

        if (! $isConfirm && ! $isCancel) {
            return null;
        }

        $result = $isConfirm
            ? $this->executePendingCreateAction($promptRequest, $user, $pending, $channel)
            : $this->cancelPendingAction($promptRequest, $pending);

        return [
            'prompt_request_id' => $promptRequest->id,
            'human_response' => $result['reply'],
        ];
    }

    private function latestPendingConfirmation(User $user): ?PromptAction
    {
        return PromptAction::query()
            ->whereHas('promptRequest', fn ($query) => $query->where('user_id', $user->id))
            ->where('action_type', 'create')
            ->where('status', 'pending_confirmation')
            ->latest()
            ->first();
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
        $asksTomorrow = preg_match('/\b(besok|bsk|tomorrow)\b/u', $text) === 1;
        $asksMine = preg_match('/\b(gua|gue|gw|aku|saya|ku)\b/u', $text) === 1;
        $asksList = preg_match('/\b(cek|lihat|list|apa|apa aja|apa saja|mana|yang)\b/u', $text) === 1;
        $isMutation = preg_match('/\b(buat|bikin|tambah|tambahkan|jadwalkan|hapus|ubah|pindah)\b/u', $text) === 1;

        if ($isMutation || ! ($asksTasks || $asksSchedule || $asksOverdue) || ! ($asksList || $asksMine || $asksPending || $asksToday || $asksOverdue)) {
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
            if ($asksSchedule && ! $asksTasks) {
                return $this->todayScheduleReply($user, $today);
            }

            $items = Task::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->whereDate('scheduled_date', $today)
                ->whereNull('scheduled_time')
                ->orderBy('scheduled_date')
                ->limit(10)
                ->get();

            if ($items->isEmpty()) {
                return 'Task hari ini kosong.';
            }

            $lines = $items->values()->map(fn (Task $task, int $index) => ($index + 1).'. '.$task->title)->implode("\n");

            return "Task hari ini:\n{$lines}";
        }

        if ($asksTomorrow && ($asksTasks || $asksSchedule)) {
            $tomorrow = Carbon::parse($today)->addDay()->format('Y-m-d');
            $items = Task::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->whereDate('scheduled_date', $tomorrow)
                ->when($asksTasks && ! $asksSchedule, fn ($query) => $query->whereNull('scheduled_time'))
                ->when($asksSchedule && ! $asksTasks, fn ($query) => $query->whereNotNull('scheduled_time'))
                ->orderByRaw('CASE WHEN scheduled_time IS NULL THEN 1 ELSE 0 END')
                ->orderBy('scheduled_time')
                ->limit(10)
                ->get();

            if ($items->isEmpty()) {
                return $asksTasks
                    ? 'Task kamu buat besok kosong bro 👌'
                    : 'Besok belum ada jadwal ya bro 😌';
            }

            $lines = $items->values()->map(function (Task $task, int $index) {
                $suffix = $task->scheduled_time
                    ? ' - '.substr((string) $task->scheduled_time, 0, 5)
                    : ' - tanpa jam';

                return ($index + 1).'. '.$task->title.$suffix;
            })->implode("\n");

            return $asksTasks
                ? "Task kamu buat besok:\n{$lines}"
                : "Jadwal kamu besok:\n{$lines}";
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

                $listLabel = $task->google_task_list_title
                    ? ' ['.$task->google_task_list_title.']'
                    : '';

                return ($index + 1).'. '.$task->title.$when.$listLabel;
            })->implode("\n");

            return "Task kamu yang belum kelar:\n{$lines}";
        }

        if ($asksSchedule || $asksToday) {
            return $this->todayScheduleReply($user, $today);
        }

        return null;
    }

    private function todayScheduleReply(User $user, string $date): string
    {
        $events = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();
        $scheduledTasks = $this->agendaQueryService->dayAgenda($user, $date)->whereNotNull('scheduled_time');

        $items = $events->map(fn (CalendarEvent $event) => [
            'title' => $event->title,
            'time' => $event->all_day ? 'seharian' : $event->starts_at->format('H:i'),
            'type' => 'event',
        ])->concat($scheduledTasks->map(fn (Task $task) => [
            'title' => $task->title,
            'time' => substr((string) $task->scheduled_time, 0, 5),
            'type' => 'jadwal',
        ]))->sortBy('time')->values();

        if ($items->isEmpty()) {
            return 'Hari ini belum ada jadwal.';
        }

        $lines = $items->map(fn (array $item, int $index) => ($index + 1).'. '.$item['title'].' - '.$item['time'].' ('.$item['type'].')')->implode("\n");

        return "Jadwal hari ini:\n{$lines}";
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
            'create' => ($data['entity_type'] ?? 'task') === 'event'
                ? $this->executeCreateEvent($promptRequest, $user, $data)
                : $this->executeCreate($promptRequest, $user, $data, $channel),
            'read' => $this->executeRead($user, $data, $today),
            'update' => $this->executeUpdate($promptRequest, $user, $taskId, $data, $channel),
            'delete' => $this->executeDelete($promptRequest, $user, $taskId, $channel),
            'complete' => $this->executeComplete($promptRequest, $user, $taskId, $channel),
            'confirm_create' => $this->storePendingCreate($promptRequest, $data),
            'confirm' => $this->executePendingConfirmation($promptRequest, $user, $channel),
            'cancel' => $this->cancelPendingConfirmation($promptRequest, $user),
            default => null,
        };
    }


    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function storePendingCreate(PromptRequest $promptRequest, array $data): array
    {
        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'create',
            'target_entity_type' => 'task',
            'status' => 'pending_confirmation',
            'payload' => $this->normalizeCreatePayload($data),
        ]);

        return ['action' => 'pending_create_confirmation', 'data' => $this->normalizeCreatePayload($data)];
    }

    private function executePendingConfirmation(PromptRequest $promptRequest, User $user, string $channel): array
    {
        $pending = $this->latestPendingConfirmation($user);

        if (! $pending) {
            return ['action' => 'confirm_pending', 'reply' => 'Belum ada event/task yang nunggu konfirmasi bro.'];
        }

        return $this->executePendingCreateAction($promptRequest, $user, $pending, $channel);
    }

    private function cancelPendingConfirmation(PromptRequest $promptRequest, User $user): array
    {
        $pending = $this->latestPendingConfirmation($user);

        if (! $pending) {
            return ['action' => 'cancel_pending', 'reply' => 'Belum ada yang perlu dibatalin bro.'];
        }

        return $this->cancelPendingAction($promptRequest, $pending);
    }

    private function executePendingCreateAction(PromptRequest $promptRequest, User $user, PromptAction $pending, string $channel): array
    {
        $data = $this->normalizeCreatePayload($pending->payload ?? []);
        $task = $this->taskMutationService->create($user, $data, $channel, $promptRequest->id);

        $pending->update([
            'status' => 'executed',
            'target_entity_id' => $task->id,
            'result_payload' => ['task_id' => $task->id, 'confirmed_by_prompt_request_id' => $promptRequest->id],
        ]);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'confirm_create',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => $data,
            'result_payload' => ['task_id' => $task->id, 'pending_action_id' => $pending->id],
        ]);

        $when = $data['scheduled_date'] ? ' tanggal '.$data['scheduled_date'] : '';
        $time = $data['scheduled_time'] ? ' jam '.substr((string) $data['scheduled_time'], 0, 5) : '';

        return [
            'action' => 'confirm_create_task',
            'task_id' => $task->id,
            'reply' => 'Siap, '.$task->title.$when.$time.' udah aku tambahin ke kalender 👍',
        ];
    }

    private function cancelPendingAction(PromptRequest $promptRequest, PromptAction $pending): array
    {
        $pending->update([
            'status' => 'cancelled',
            'result_payload' => ['cancelled_by_prompt_request_id' => $promptRequest->id],
        ]);

        return ['action' => 'cancel_pending_create', 'reply' => 'Oke, ga aku tambahin ke kalender ya bro.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCreatePayload(array $data): array
    {
        return [
            'title' => $data['title'] ?? 'Task baru',
            'description' => $data['description'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'scheduled_time' => $data['scheduled_time'] ?? null,
            'all_day' => $data['all_day'] ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function executeCreateEvent(PromptRequest $promptRequest, User $user, array $data): array
    {
        $date = $data['scheduled_date'] ?? now()->format('Y-m-d');
        $time = $data['scheduled_time'] ?? null;
        $allDay = (bool) ($data['all_day'] ?? $time === null);
        $startsAt = Carbon::parse($date.' '.($time ?: '00:00:00'), 'Asia/Jakarta');

        $event = CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'] ?? 'Event baru',
            'description' => $data['description'] ?? null,
            'starts_at' => $startsAt,
            'timezone' => 'Asia/Jakarta',
            'all_day' => $allDay,
        ]);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'create',
            'target_entity_type' => 'event',
            'target_entity_id' => $event->id,
            'status' => 'executed',
            'payload' => $data,
            'result_payload' => ['event_id' => $event->id],
        ]);

        return ['action' => 'create_event', 'event_id' => $event->id];
    }

    private function executeCreate(PromptRequest $promptRequest, User $user, array $data, string $channel): array
    {
        $data = $this->normalizeCreatePayload($data);

        $task = $this->taskMutationService->create($user, $data, $channel, $promptRequest->id);

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
