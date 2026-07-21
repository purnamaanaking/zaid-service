<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Models\PromptAction;
use App\Models\PromptRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class PromptCommandService
{
    public function __construct(private readonly PromptParser $parser) {}

    public function process(User $user, string $text, string $channel = 'app_prompt', ?array $attachments = null, ?string $selectedDate = null): array
    {
        $parsed = $this->parser->parse($text, $user->id, $attachments);
        $entities = $parsed['entities'] ?? [];
        if ($selectedDate !== null && empty($entities['scheduled_date'])) $entities['scheduled_date'] = $selectedDate;
        $request = PromptRequest::query()->create(['user_id' => $user->id, 'channel' => $channel, 'raw_text' => $text, 'normalized_text' => $text, 'intent' => $parsed['intent'] ?? null, 'confidence_score' => $parsed['confidence_score'] ?? 0, 'parse_status' => $parsed['parse_status'] ?? 'failed', 'extracted_entities' => $entities, 'execution_status' => 'pending']);
        $intent = $parsed['intent'] ?? 'READ';
        if (($parsed['parse_status'] ?? 'failed') !== 'parsed') return $this->finish($request, 'failed', 'Aku fokus bantu agenda dan event. Coba tulis: "buat meeting besok jam 9".');
        if ($intent === 'READ') {
            $events = CalendarEvent::query()->where('user_id', $user->id)->when($entities['scheduled_date'] ?? null, fn ($q, $date) => $q->whereDate('starts_at', $date))->orderBy('starts_at')->get();
            $reply = $events->isEmpty() ? 'Belum ada agenda.' : "Agenda kamu:\n".$events->map(fn ($event, $i) => ($i + 1).'. '.$event->title.' - '.$event->starts_at->format('Y-m-d H:i'))->implode("\n");
            return $this->finish($request, 'executed', $reply);
        }
        if ($intent === 'CREATE') {
            $event = $this->event($user, $entities);
            $action = PromptAction::query()->create(['prompt_request_id' => $request->id, 'action_type' => 'create', 'target_entity_type' => 'event', 'target_entity_id' => $event->id, 'status' => 'executed', 'payload' => $entities, 'result_payload' => ['event_id' => $event->id]]);
            return $this->finish($request, 'executed', "{$event->title} sudah masuk agenda.", ['event_id' => $event->id]);
        }
        $event = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('title', 'ilike', '%'.($entities['search_query'] ?? $entities['title'] ?? '').'%')
            ->when($entities['scheduled_date'] ?? null, fn ($query, $date) => $query->whereDate('starts_at', $date))
            ->latest('starts_at')
            ->first();
        if (! $event) return $this->finish($request, 'failed', 'Event yang dimaksud belum ketemu.');
        if ($intent === 'DELETE') $event->delete(); else $event->update($this->payload($entities));
        PromptAction::query()->create(['prompt_request_id' => $request->id, 'action_type' => strtolower($intent), 'target_entity_type' => 'event', 'target_entity_id' => $event->id, 'status' => 'executed', 'payload' => $entities, 'result_payload' => ['event_id' => $event->id]]);
        return $this->finish($request, 'executed', $intent === 'DELETE' ? 'Event sudah dihapus.' : 'Event sudah diubah.', ['event_id' => $event->id]);
    }

    public function confirm(PromptRequest $request, User $user, bool $confirmed): array { return $this->finish($request, 'executed', $confirmed ? 'Tidak ada aksi yang menunggu konfirmasi.' : 'Dibatalkan.'); }
    private function event(User $user, array $entities): CalendarEvent { return CalendarEvent::query()->create(['user_id' => $user->id] + $this->payload($entities)); }
    private function payload(array $e): array { $date = $e['scheduled_date'] ?? now()->format('Y-m-d'); $time = $e['scheduled_time'] ?? '09:00:00'; return ['title' => $e['title'] ?? 'Event baru', 'description' => $e['description'] ?? null, 'starts_at' => Carbon::parse("$date $time", 'Asia/Jakarta'), 'timezone' => 'Asia/Jakarta', 'all_day' => (bool) ($e['all_day'] ?? false)]; }
    private function finish(PromptRequest $request, string $status, string $reply, array $result = []): array { $request->update(['execution_status' => $status, 'execution_summary' => ['human_response' => $reply] + $result]); return ['prompt_request_id' => $request->id, 'parse_status' => $request->parse_status, 'intent' => $request->intent, 'requires_confirmation' => false, 'result' => $result, 'human_response' => $reply]; }
}
