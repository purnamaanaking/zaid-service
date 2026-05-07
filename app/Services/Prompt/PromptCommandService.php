<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
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
    public function process(User $user, string $text, string $channel = 'app_prompt', ?array $attachments = null): array
    {
        $parsed = $this->parser->parse($text, $user->id, $attachments);

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
            $promptRequest->update(['execution_status' => 'failed']);

            return [
                'prompt_request_id' => $promptRequest->id,
                'parse_status' => 'failed',
                'intent' => null,
                'requires_confirmation' => false,
                'result' => null,
                'human_response' => 'Maaf ya, aku masih belum nangkep maksud perintahmu. Coba tulis lebih spesifik, misalnya "cek jadwal hari ini" atau "buat task meeting besok jam 9".',
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
                'human_response' => 'Aku belum bisa bantu untuk permintaan itu lewat WhatsApp. Coba minta cek jadwal, buat task, ubah task, atau hapus task ya.',
            ];
        }

        if ($parsed['requires_confirmation'] ?? false) {
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
                'CREATE' => $this->executeCreate($promptRequest, $user, $entities, $channel),
                'READ' => $this->executeRead($user, $entities),
                'UPDATE' => $this->executeUpdate($promptRequest, $user, $entities, $channel),
                'DELETE' => $this->executeDelete($promptRequest, $user, $entities, $channel),
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

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeRead(User $user, array $entities): array
    {
        $date = $entities['scheduled_date'] ?? now()->format('Y-m-d');
        $items = $this->agendaQueryService->dayAgenda($user, $date);

        if ($items->isEmpty()) {
            return [
                'action' => 'read_agenda',
                'items' => [],
                'human_response' => "Belum ada jadwal untuk tanggal {$date}.",
            ];
        }

        $lines = $items->map(fn (Task $t, int $i) => ($i + 1).". {$t->title}".($t->scheduled_time ? " - {$t->scheduled_time}" : ''))->implode("\n");

        return [
            'action' => 'read_agenda',
            'date' => $date,
            'items' => $items->toArray(),
            'human_response' => "Jadwal kamu di tanggal {$date}:\n{$lines}",
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeUpdate(PromptRequest $promptRequest, User $user, array $entities, string $channel): array
    {
        $matches = $this->resolveTaskCandidates($user, $entities);

        if ($matches->isEmpty()) {
            return ['action' => 'update_task', 'human_response' => 'Aku belum nemu task mana yang mau kamu ubah. Coba sebut judul atau jamnya lebih jelas ya.'];
        }

        if ($matches->count() > 1) {
            return [
                'action' => 'update_task',
                'requires_confirmation' => true,
                'candidates' => $matches->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'scheduled_date' => $task->scheduled_date?->format('Y-m-d'),
                    'scheduled_time' => $task->scheduled_time,
                ])->all(),
                'human_response' => "Aku nemu beberapa task yang mirip. Maksudmu yang mana?\n".$matches->values()->map(fn (Task $task, int $index) => ($index + 1).'. '.$task->title.($task->scheduled_time ? ' - '.$task->scheduled_time : ''))->implode("\n"),
            ];
        }

        $task = $this->taskMutationService->update($matches->first(), $user, $entities, $channel, $promptRequest->id);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'update',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => $entities,
            'result_payload' => ['task_id' => $task->id],
        ]);

        return [
            'action' => 'update_task',
            'task' => $task->toArray(),
            'human_response' => "Siap, task \"{$task->title}\" udah aku update.",
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    private function executeDelete(PromptRequest $promptRequest, User $user, array $entities, string $channel): array
    {
        $matches = $this->resolveTaskCandidates($user, $entities);

        if ($matches->isEmpty()) {
            return ['action' => 'delete_task', 'human_response' => 'Aku belum nemu task mana yang mau kamu hapus. Coba sebut judul atau jamnya lebih jelas ya.'];
        }

        if ($matches->count() > 1) {
            return [
                'action' => 'delete_task',
                'requires_confirmation' => true,
                'candidates' => $matches->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'scheduled_date' => $task->scheduled_date?->format('Y-m-d'),
                    'scheduled_time' => $task->scheduled_time,
                ])->all(),
                'human_response' => "Aku nemu beberapa task yang mirip. Maksudmu yang mana mau dihapus?\n".$matches->values()->map(fn (Task $task, int $index) => ($index + 1).'. '.$task->title.($task->scheduled_time ? ' - '.$task->scheduled_time : ''))->implode("\n"),
            ];
        }

        $task = $matches->first();
        $title = $task->title;
        $this->taskMutationService->delete($task, $user, $channel, $promptRequest->id);

        PromptAction::query()->create([
            'prompt_request_id' => $promptRequest->id,
            'action_type' => 'delete',
            'target_entity_type' => 'task',
            'target_entity_id' => $task->id,
            'status' => 'executed',
            'payload' => $entities,
            'result_payload' => ['task_id' => $task->id],
        ]);

        return [
            'action' => 'delete_task',
            'human_response' => "Oke, task \"{$title}\" udah aku hapus.",
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return EloquentCollection<int, Task>
     */
    private function resolveTaskCandidates(User $user, array $entities): EloquentCollection
    {
        $query = Task::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at');

        if (! empty($entities['title'])) {
            $query->where('title', 'ilike', '%'.$entities['title'].'%');
        } elseif (! empty($entities['search_query'])) {
            $normalized = str_replace('.', ':', (string) $entities['search_query']);
            $query->where(function ($builder) use ($normalized) {
                $builder->where('title', 'ilike', '%'.$normalized.'%')
                    ->orWhere('description', 'ilike', '%'.$normalized.'%')
                    ->orWhereRaw("to_char(scheduled_time, 'HH24:MI:SS') LIKE ?", ['%'.$normalized.'%'])
                    ->orWhereRaw("to_char(scheduled_time, 'HH24:MI') LIKE ?", ['%'.$normalized.'%']);
            });
        }

        if (! empty($entities['scheduled_date'])) {
            $query->whereDate('scheduled_date', $entities['scheduled_date']);
        }

        return $query->orderBy('scheduled_date')->orderBy('scheduled_time')->limit(5)->get();
    }
}
