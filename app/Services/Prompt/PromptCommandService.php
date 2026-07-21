<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Models\PromptAction;
use App\Models\PromptRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Services\Agenda\AgendaQueryService;
use App\Services\Tasks\TaskMutationService;
use Illuminate\Support\Facades\DB;

class PromptCommandService
{
    public function __construct(
        private readonly PromptParser $parser,
        private readonly TaskMutationService $taskMutationService,
        private readonly AgendaQueryService $agendaQueryService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<int, array{type: string, url?: string, mime_type?: string, text?: string}>|null  $attachments
     */
    public function process(User $user, string $text, string $channel = 'app_prompt', ?array $attachments = null, ?string $selectedDate = null): array
    {
        $parsed = $this->parser->parse($this->conversationContext($user, $text), $user->id, $attachments);
        if ($selectedDate !== null) {
            $parsed['entities']['scheduled_date'] = $selectedDate;
        }

        $promptRequest = PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'raw_text' => $text,
            'normalized_text' => $text,
            'intent' => $parsed['intent'] ?? null,
            'confidence_score' => $parsed['confidence_score'] ?? 0,
            'parse_status' => $parsed['parse_status'] ?? 'failed',
            'extracted_entities' => $parsed['entities'] ?? null,
            'execution_status' => 'pending',
        ]);

        if (($parsed['parse_status'] ?? 'failed') === 'failed') {
            if ($deleteResult = $this->quickDelete($promptRequest, $user, $text, $channel)) {
                return $deleteResult;
            }

            if ($createResult = $this->quickCreate($promptRequest, $user, $text, $channel)) {
                return $createResult;
            }

            if ($reply = $this->quickReply($user, $text)) {
                $promptRequest->update([
                    'intent' => 'READ',
                    'parse_status' => 'parsed',
                    'execution_status' => 'executed',
                    'execution_summary' => ['human_response' => $reply],
                ]);

                return [
                    'prompt_request_id' => $promptRequest->id,
                    'parse_status' => 'parsed',
                    'intent' => 'READ',
                    'requires_confirmation' => false,
                    'result' => null,
                    'human_response' => $reply,
                ];
            }

            $promptRequest->update(['execution_status' => 'failed']);

            return [
                'prompt_request_id' => $promptRequest->id,
                'parse_status' => 'failed',
                'intent' => null,
                'requires_confirmation' => false,
                'result' => null,
                'human_response' => 'Aku belum nangkep. Coba tulis, misalnya "buat meeting besok jam 9" atau "cek jadwal hari ini".',
            ];
        }

        if (($parsed['parse_status'] ?? '') === 'unsupported') {
            $promptRequest->update(['execution_status' => 'rejected']);

            return [
                'prompt_request_id' => $promptRequest->id,
                'parse_status' => 'unsupported',
                'intent' => $parsed['intent'],
                'requires_confirmation' => false,
                'result' => null,
                'human_response' => 'Aku belum bisa bantu untuk permintaan itu. Coba cek, buat, ubah, atau hapus jadwal ya.',
            ];
        }

        $clearDestructiveMatch = in_array(strtoupper((string) ($parsed['intent'] ?? '')), ['UPDATE', 'DELETE'], true)
            && $this->resolveEventCandidates($user, $parsed['entities'] ?? [])->count() === 1;

        if (($parsed['requires_confirmation'] ?? false) && ! $clearDestructiveMatch) {
            $promptRequest->update(['execution_status' => 'awaiting_confirmation']);

            return [
                'prompt_request_id' => $promptRequest->id,
                'parse_status' => $parsed['parse_status'],
                'intent' => $parsed['intent'],
                'confidence_score' => $parsed['confidence_score'],
                'requires_confirmation' => true,
                'confirmation' => [
                    'question' => 'Biar aman, aku konfirmasi dulu ya. Ini maksudmu sudah benar belum?',
                    'entities' => $parsed['entities'],
                ],
                'result' => null,
                'human_response' => 'Biar aman, aku konfirmasi dulu ya. Ini maksudmu sudah benar belum?',
            ];
        }

        return $this->execute($promptRequest, $user, $parsed, $channel);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function quickCreate(PromptRequest $promptRequest, User $user, string $text, string $channel): ?array
    {
        $normalized = strtolower(trim($text));
        if (preg_match('/\b(buat|bikin|tambah|tambahkan|catat|jadwalkan)\b/u', $normalized) !== 1) {
            return null;
        }

        $date = match (true) {
            preg_match('/\b(besok|bsk|tomorrow)\b/u', $normalized) === 1 => now()->addDay()->format('Y-m-d'),
            preg_match('/\b(lusa)\b/u', $normalized) === 1 => now()->addDays(2)->format('Y-m-d'),
            default => now()->format('Y-m-d'),
        };
        $time = null;
        if (preg_match('/\bjam\s+(\d{1,2})(?::(\d{2}))?\s*(pagi|siang|sore|malam)?\b/u', $normalized, $match)) {
            $hour = (int) $match[1];
            $minute = isset($match[2]) ? (int) $match[2] : 0;
            $period = $match[3] ?? null;
            if ($period === 'malam' && $hour < 12) $hour += 12;
            if ($period === 'siang' && $hour < 11) $hour += 12;
            if ($period === 'sore' && $hour < 12) $hour += 12;
            $time = sprintf('%02d:%02d:00', $hour, $minute);
        }

        $title = preg_replace('/\b(buat|bikin|tambah|tambahkan|catat|jadwalkan|task|tugas|todo|baru|besok|bsk|tomorrow|lusa|hari ini|today|jam\s+\d{1,2}(?::\d{2})?|pagi|siang|sore|malam)\b/u', ' ', $normalized);
        $title = trim(preg_replace('/\s+/', ' ', (string) $title));
        if ($title === '') return null;

        $data = [
            'title' => ucfirst($title),
            'description' => null,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'all_day' => $time === null,
        ];
        $result = $this->executeCreateEvent($promptRequest, $user, $data);
        $promptRequest->update([
            'intent' => 'CREATE',
            'parse_status' => 'parsed',
            'execution_status' => 'executed',
            'extracted_entities' => $data,
            'execution_summary' => $result,
        ]);

        return [
            'prompt_request_id' => $promptRequest->id,
            'parse_status' => 'parsed',
            'intent' => 'CREATE',
            'requires_confirmation' => false,
            'result' => $result,
            'human_response' => $result['human_response'],
        ];
    }

    private function quickDelete(PromptRequest $promptRequest, User $user, string $text, string $channel): ?array
    {
        $normalized = strtolower(trim($text));
        if (preg_match('/\b(hapus|delete|buang|remove|batal|batalkan)\b/u', $normalized) !== 1) {
            return null;
        }

        $target = preg_replace('/\b(tolong|minta|hapus|delete|buang|remove|batal|batalkan|task|tugas|jadwal|agenda|yang|ini|itu|tadi|bro|bang|kak|dong|ya)\b/u', ' ', $normalized);
        $target = trim(preg_replace('/\s+/', ' ', (string) $target));

        if ($target === '') {
            $previous = PromptRequest::query()
                ->where('user_id', $user->id)
                ->where('channel', 'app_prompt')
                ->where('id', '!=', $promptRequest->id)
                ->latest()
                ->value('raw_text');
            $target = preg_replace('/\b(tolong|minta|hapus|delete|buang|remove|batal|batalkan|task|tugas|jadwal|agenda|yang|ini|itu|tadi|bro|bang|kak|dong|ya)\b/u', ' ', strtolower((string) $previous));
            $target = trim(preg_replace('/\s+/', ' ', (string) $target));
        }

        if ($target === '') {
            return null;
        }

        $matches = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('title', 'ilike', '%'.$target.'%')
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        if ($matches->count() !== 1) {
            return null;
        }

        $event = $matches->first();
        $title = $event->title;
        $event->reminders()->where('status', 'pending')->delete();
        $event->delete();
        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'delete',
            'target_entity_type' => 'event',
            'target_entity_id' => $event->id,
            'status' => 'executed',
            'payload' => ['title' => $title, 'mode' => 'quick_context_delete'],
            'result_payload' => ['event_id' => $event->id],
        ]);
        $result = ['action' => 'delete_event', 'human_response' => "Jadwal \"{$title}\" sudah dihapus."];
        $promptRequest->update([
            'intent' => 'DELETE',
            'parse_status' => 'parsed',
            'execution_status' => 'executed',
            'execution_summary' => $result,
        ]);

        return [
            'prompt_request_id' => $promptRequest->id,
            'parse_status' => 'parsed',
            'intent' => 'DELETE',
            'requires_confirmation' => false,
            'result' => $result,
            'human_response' => $result['human_response'],
        ];
    }

    private function quickReply(User $user, string $text): ?string
    {
        $text = strtolower(trim($text));

        if (preg_match('/^\s*(halo|hai|hi|bro)\s*[!.?]*\s*$/u', $text)) {
            return 'Halo bro. Mau cek atau buat jadwal?';
        }

        $asksRead = preg_match('/\b(cek|lihat|list|apa|mana|ada|jadwal|agenda)\b/u', $text) === 1;
        $date = match (true) {
            preg_match('/\b(kemarin|yesterday)\b/u', $text) === 1 => now()->subDay()->format('Y-m-d'),
            preg_match('/\b(besok|bsk|tomorrow)\b/u', $text) === 1 => now()->addDay()->format('Y-m-d'),
            preg_match('/\b(hari ini|today|sekarang)\b/u', $text) === 1 => now()->format('Y-m-d'),
            default => null,
        };

        if ($asksRead && $date !== null && preg_match('/\b(jadwal|agenda|calendar|kalender)\b/u', $text) === 1) {
            return $this->readDayTasksAndEvents($user, $date)['human_response'];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function confirm(PromptRequest $promptRequest, User $user, bool $confirmed): array
    {
        if (! $confirmed) {
            $promptRequest->update(['execution_status' => 'rejected']);

            return [
                'prompt_request_id' => $promptRequest->id,
                'result' => null,
                'human_response' => 'Oke, aku batalin dulu ya.',
            ];
        }

        $parsed = [
            'intent' => $promptRequest->intent,
            'confidence_score' => $promptRequest->confidence_score,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => $promptRequest->extracted_entities,
        ];

        return $this->execute($promptRequest, $user, $parsed, $promptRequest->channel);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function execute(PromptRequest $promptRequest, User $user, array $parsed, string $channel): array
    {
        $intent = strtoupper($parsed['intent']);
        $entities = $parsed['entities'] ?? [];

        return DB::transaction(function () use ($promptRequest, $user, $intent, $entities, $channel): array {
            $result = match ($intent) {
                'CREATE' => $this->executeCreateEvent($promptRequest, $user, $entities),
                'READ' => $this->executeRead($user, $entities),
                'UPDATE' => $this->executeUpdateEvent($promptRequest, $user, $entities),
                'DELETE' => $this->executeDeleteEvent($promptRequest, $user, $entities),
                default => ['action' => 'unknown', 'human_response' => 'Maaf, aku belum ngerti maksud perintah itu.'],
            };

            $promptRequest->update([
                'execution_status' => 'executed',
                'execution_summary' => $result,
            ]);

            return [
                'prompt_request_id' => $promptRequest->id,
                'parse_status' => 'parsed',
                'intent' => $intent,
                'confidence_score' => $parsed['confidence_score'] ?? 0,
                'requires_confirmation' => false,
                'result' => $result,
                'human_response' => $result['human_response'] ?? null,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeCreateEvent(PromptRequest $promptRequest, User $user, array $entities): array
    {
        $date = $entities['scheduled_date'] ?? now()->format('Y-m-d');
        $time = $entities['scheduled_time'] ?? '09:00:00';
        $event = CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => $entities['title'] ?? 'Event baru',
            'description' => $entities['description'] ?? null,
            'starts_at' => $date.' '.$time,
            'timezone' => 'Asia/Jakarta',
            'all_day' => $entities['all_day'] ?? false,
        ]);

        $this->createReminderFromEntitiesForEvent($user, $event, $entities);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'create',
            'target_entity_type' => 'event',
            'target_entity_id' => $event->id,
            'status' => 'executed',
            'payload' => $entities,
            'result_payload' => ['event_id' => $event->id],
        ]);

        return [
            'action' => 'create_event',
            'event' => $event->toArray(),
            'human_response' => 'Event "'.$event->title.'" sudah masuk kalender.',
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeCreate(PromptRequest $promptRequest, User $user, array $entities, string $channel): array
    {
        $task = $this->taskMutationService->create($user, [
            'title' => $entities['title'] ?? 'Untitled task',
            'description' => $entities['description'] ?? null,
            'scheduled_date' => $entities['scheduled_date'] ?? null,
            'scheduled_time' => $entities['scheduled_time'] ?? null,
            'all_day' => $entities['all_day'] ?? false,
            'recurrence' => $entities['recurrence'] ?? null,
        ], $channel, $promptRequest->id);
        $this->createReminderFromEntities($user, $task, $entities);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'create',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => $entities,
            'result_payload' => ['task_id' => $task->id],
        ]);

        $recurrenceText = '';
        if ($task->is_recurring && $task->recurrence) {
            $recurrenceText = " (berulang {$task->recurrence->recurrence_type})";
        }

        return [
            'action' => 'create_task',
            'task' => $task->toArray(),
            'human_response' => "Siap, aku udah buatin task \"{$task->title}\"{$recurrenceText}.",
        ];
    }

    private function createReminderFromEntitiesForEvent(User $user, CalendarEvent $event, array $entities): void
    {
        $minutes = $entities['reminder_minutes_before'] ?? null;
        if (! is_numeric($minutes) || ! $event->starts_at) return;
        $minutes = (int) $minutes;
        if ($minutes < 1 || $minutes > 525600) return;
        $channel = $entities['reminder_channel'] ?? 'whatsapp';
        if (! in_array($channel, ['whatsapp', 'app', 'both'], true)) $channel = 'whatsapp';
        \App\Models\Reminder::query()->create([
            'user_id' => $user->id,
            'calendar_event_id' => $event->id,
            'minutes_before' => $minutes,
            'channel' => $channel,
            'remind_at' => $event->starts_at->copy()->subMinutes($minutes),
        ]);
    }

    private function createReminderFromEntities(User $user, Task $task, array $entities): void
    {
        $minutes = $entities['reminder_minutes_before'] ?? null;
        if (! is_numeric($minutes) || ! $task->scheduled_date || ! $task->scheduled_time) return;
        $minutes = (int) $minutes;
        if ($minutes < 1 || $minutes > 525600) return;
        $channel = $entities['reminder_channel'] ?? 'whatsapp';
        if (! in_array($channel, ['whatsapp', 'app', 'both'], true)) $channel = 'whatsapp';
        $startsAt = $task->scheduled_date->format('Y-m-d').' '.$task->scheduled_time;
        \App\Models\Reminder::query()->create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'minutes_before' => $minutes,
            'channel' => $channel,
            'remind_at' => now()->parse($startsAt, $task->timezone)->subMinutes($minutes),
        ]);
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeRead(User $user, array $entities): array
    {
        $search = strtolower((string) ($entities['search_query'] ?? ''));

        if (preg_match('/\b(task|tugas)\b/u', $search) === 1 && preg_match('/\b(jadwal|agenda|kalender|calendar)\b/u', $search) === 1 && preg_match('/\b(hari ini|today|sekarang)\b/u', $search) === 1) {
            return $this->readDayTasksAndEvents($user, now()->format('Y-m-d'));
        }

        if (preg_match('/\b(?:1|satu)\s+minggu\s+(?:terakhir|kebelakang)\b|\b7\s+hari\s+terakhir\b/u', $search) === 1) {
            $end = now()->startOfDay();
            $start = $end->copy()->subDays(6);
            $items = Task::query()
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->whereBetween('scheduled_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->where('status', '!=', 'cancelled')
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->get();

            if ($items->isEmpty()) {
                return ['action' => 'read_agenda', 'items' => [], 'human_response' => 'Belum ada jadwal dalam 7 hari terakhir.'];
            }

            $lines = $items->values()->map(fn (Task $task, int $index) => ($index + 1).'. '.$task->title.' - '.$task->scheduled_date->format('Y-m-d').($task->scheduled_time ? ' '.substr((string) $task->scheduled_time, 0, 5) : ''))->implode("\n");

            return [
                'action' => 'read_agenda',
                'date_from' => $start->format('Y-m-d'),
                'date_to' => $end->format('Y-m-d'),
                'items' => $items->toArray(),
                'human_response' => "Jadwal 7 hari terakhir:\n{$lines}",
            ];
        }

        return $this->readDayTasksAndEvents($user, $entities['scheduled_date'] ?? now()->format('Y-m-d'));
    }

    private function readDayTasksAndEvents(User $user, string $date): array
    {
        $events = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();

        if ($events->isEmpty()) {
            return ['action' => 'read_agenda', 'items' => [], 'human_response' => "Belum ada jadwal untuk tanggal {$date}."];
        }

        $lines = $events->map(fn (CalendarEvent $event) => '- '.$event->title.' - '.($event->all_day ? 'seharian' : $event->starts_at->format('H:i')))->implode("\n");
        return ['action' => 'read_agenda', 'date' => $date, 'items' => $events->toArray(), 'human_response' => "Jadwal tanggal {$date}:\n{$lines}"];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeUpdateEvent(PromptRequest $promptRequest, User $user, array $entities): array
    {
        $matches = $this->resolveEventCandidates($user, $entities);

        if ($matches->isEmpty()) {
            return ['action' => 'update_event', 'human_response' => 'Aku belum nemu jadwal mana yang mau kamu ubah. Sebut judul, tanggal, atau jamnya ya.'];
        }

        if ($matches->count() > 1) {
            return [
                'action' => 'update_event',
                'requires_confirmation' => true,
                'candidates' => $matches->map(fn (CalendarEvent $event) => ['id' => $event->id, 'title' => $event->title, 'starts_at' => $event->starts_at?->toIso8601String()])->all(),
                'human_response' => "Aku nemu beberapa jadwal yang mirip. Maksudmu yang mana?",
            ];
        }

        $event = $matches->first();
        $date = $entities['scheduled_date'] ?? $event->starts_at?->format('Y-m-d');
        $time = $entities['scheduled_time'] ?? $event->starts_at?->format('H:i:s');
        $event->update(array_filter([
            'title' => $entities['title'] ?? null,
            'description' => $entities['description'] ?? null,
            'starts_at' => $date && $time ? $date.' '.$time : null,
            'all_day' => $entities['all_day'] ?? null,
        ], fn ($value) => $value !== null));
        $event->reminders()->where('status', 'pending')->each(fn ($reminder) => $reminder->update(['remind_at' => $event->starts_at->copy()->subMinutes($reminder->minutes_before)]));

        return ['action' => 'update_event', 'event' => $event->fresh()->toArray(), 'human_response' => "Jadwal \"{$event->title}\" sudah diperbarui."];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeDeleteEvent(PromptRequest $promptRequest, User $user, array $entities): array
    {
        $matches = $this->resolveEventCandidates($user, $entities);
        if ($matches->isEmpty()) {
            return ['action' => 'delete_event', 'human_response' => 'Aku belum nemu jadwal mana yang mau dihapus. Sebut judul, tanggal, atau jamnya ya.'];
        }
        if ($matches->count() > 1) {
            return ['action' => 'delete_event', 'requires_confirmation' => true, 'candidates' => $matches->map(fn (CalendarEvent $event) => ['id' => $event->id, 'title' => $event->title, 'starts_at' => $event->starts_at?->toIso8601String()])->all(), 'human_response' => 'Aku nemu beberapa jadwal yang mirip. Maksudmu yang mana?'];
        }

        $event = $matches->first();
        $event->reminders()->where('status', 'pending')->delete();
        $event->delete();
        PromptAction::query()->create(['prompt_request_id' => $promptRequest->id, 'action_type' => 'delete', 'target_entity_type' => 'event', 'target_entity_id' => $event->id, 'status' => 'executed', 'payload' => $entities, 'result_payload' => ['event_id' => $event->id]]);

        return ['action' => 'delete_event', 'human_response' => "Jadwal \"{$event->title}\" sudah dihapus."];
    }

    private function conversationContext(User $user, string $text): string
    {
        $history = PromptRequest::query()
            ->where('user_id', $user->id)
            ->where('channel', 'app_prompt')
            ->latest()
            ->limit(6)
            ->get()
            ->reverse()
            ->map(function (PromptRequest $prompt): string {
                $response = data_get($prompt->execution_summary, 'human_response');

                return "User: {$prompt->raw_text}".($response ? "\nZaid: {$response}" : '');
            })
            ->implode("\n");

        if ($history === '') {
            return $text;
        }

        return "Conversation context:\n{$history}\n\nCurrent user message: {$text}\nInterpret and act only on current user message, using context for references such as 'ini', 'itu', or 'yang tadi'.";
    }

    /** @return EloquentCollection<int, CalendarEvent> */
    private function resolveEventCandidates(User $user, array $entities): EloquentCollection
    {
        $query = CalendarEvent::query()->where('user_id', $user->id);
        if (! empty($entities['title'])) {
            $query->where('title', 'ilike', '%'.$entities['title'].'%');
        }
        if (! empty($entities['scheduled_date'])) {
            $query->whereDate('starts_at', $entities['scheduled_date']);
        }
        return $query->orderBy('starts_at')->limit(5)->get();
    }
}
