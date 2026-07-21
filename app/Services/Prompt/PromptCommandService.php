<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Models\PromptAction;
use App\Models\PromptRequest;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromptCommandService
{
    public function __construct(private readonly PromptParser $parser) {}

    public function process(User $user, string $text, string $channel = 'app_prompt', ?array $attachments = null, ?string $selectedDate = null): array
    {
        $parsed = $this->parser->parse($this->context($user, $text, $channel), $user->id, $attachments);
        $data = $parsed['entities'] ?? [];
        if ($selectedDate && empty($data['scheduled_date']) && empty($data['scheduled_dates'])) $data['scheduled_date'] = $selectedDate;
        $request = PromptRequest::query()->create(['user_id' => $user->id, 'channel' => $channel, 'raw_text' => $text, 'normalized_text' => $text, 'intent' => $parsed['intent'] ?? null, 'confidence_score' => $parsed['confidence_score'] ?? 0, 'parse_status' => $parsed['parse_status'] ?? 'failed', 'extracted_entities' => $data, 'execution_status' => 'pending']);
        if (($parsed['parse_status'] ?? '') !== 'parsed') return $this->finish($request, 'failed', 'Aku belum paham. Coba jelaskan jadwalnya lagi.');

        $action = strtoupper($data['action'] ?? $parsed['intent'] ?? 'LIST_EVENTS');
        return DB::transaction(fn () => match ($action) {
            'CREATE', 'CREATE_EVENTS' => $this->create($request, $user, $data),
            'UPDATE', 'UPDATE_EVENTS', 'RESCHEDULE_EVENTS' => $this->update($request, $user, $data),
            'DELETE', 'DELETE_EVENTS' => $this->delete($request, $user, $data),
            'SET_REMINDER', 'UPDATE_REMINDER' => $this->reminder($request, $user, $data),
            'DELETE_REMINDER' => $this->removeReminder($request, $user, $data),
            'RECAP', 'COUNT_EVENTS', 'SEARCH_EVENTS', 'CHECK_CONFLICTS', 'CHECK_AVAILABILITY', 'FIND_FREE_SLOT', 'LIST_EVENTS', 'READ' => $this->read($request, $user, $data),
            default => $this->finish($request, 'failed', $data['human_response'] ?? 'Perintah agenda belum dikenali.'),
        });
    }

    private function read(PromptRequest $request, User $user, array $data): array
    {
        $events = $this->events($user, $data);
        $items = $this->items($events);
        $fallback = $events->isEmpty() ? 'Belum ada agenda.' : "Agenda kamu:\n".$events->values()->map(fn ($e, $i) => ($i + 1).'. '.$e->title.' - '.$e->starts_at->format('Y-m-d H:i'))->implode("\n");
        return $this->finish($request, 'executed', $this->reply($data, $fallback), ['items' => $items]);
    }

    private function create(PromptRequest $request, User $user, array $data): array
    {
        $dates = $data['scheduled_dates'] ?? [$data['scheduled_date'] ?? now('Asia/Jakarta')->format('Y-m-d')];
        $events = collect($dates)->map(function ($date) use ($user, $data) { $data['scheduled_date'] = $date; return CalendarEvent::query()->create(['user_id' => $user->id] + $this->payload($data)); });
        $events->each(fn ($event) => $this->action($request, 'create', $event, $data));
        $fallback = $events->count() === 1 ? $events->first()->title.' sudah masuk agenda.' : $events->count().' jadwal "'.$events->first()->title.'" sudah masuk agenda.';
        return $this->finish($request, 'executed', $this->reply($data, $fallback), ['items' => $this->items($events)]);
    }

    private function update(PromptRequest $request, User $user, array $data): array
    {
        $events = $this->events($user, $data);
        if ($events->isEmpty()) return $this->finish($request, 'failed', 'Event yang dimaksud belum ketemu.');
        $changes = $data['changes'] ?? $data;
        $events->each(function ($event) use ($request, $changes) { $event->update($this->payload($changes, $event)); $event->reminders()->where('status','pending')->each(fn ($r) => $r->update(['remind_at' => $event->starts_at->copy()->subMinutes($r->minutes_before)])); $this->action($request, 'update', $event, $changes); });
        return $this->finish($request, 'executed', $this->reply($data, $events->count().' jadwal sudah diubah.'), ['items' => $this->items($events)]);
    }

    private function delete(PromptRequest $request, User $user, array $data): array
    {
        $events = $this->events($user, $data);
        if ($events->isEmpty()) return $this->finish($request, 'failed', 'Event yang dimaksud belum ketemu.');
        $events->each(function ($event) use ($request, $data) { $event->reminders()->where('status','pending')->delete(); $event->delete(); $this->action($request, 'delete', $event, $data); });
        return $this->finish($request, 'executed', $this->reply($data, $events->count().' jadwal sudah dihapus.'));
    }

    private function reminder(PromptRequest $request, User $user, array $data): array
    {
        $events = $this->events($user, $data);
        if ($events->isEmpty()) return $this->finish($request, 'failed', 'Event untuk reminder belum ketemu.');
        $minutes = (int) ($data['reminder_minutes_before'] ?? data_get($data, 'reminder.minutes_before', 30));
        $channel = $data['reminder_channel'] ?? data_get($data, 'reminder.channel', 'whatsapp');
        $events->each(fn ($event) => Reminder::query()->updateOrCreate(['user_id' => $user->id, 'calendar_event_id' => $event->id], ['minutes_before' => $minutes, 'channel' => $channel, 'remind_at' => $event->starts_at->copy()->subMinutes($minutes), 'status' => 'pending']));
        return $this->finish($request, 'executed', $this->reply($data, 'Reminder diatur untuk '.$events->count().' jadwal.'));
    }

    private function removeReminder(PromptRequest $request, User $user, array $data): array { $events = $this->events($user, $data); $events->each(fn ($e) => $e->reminders()->delete()); return $this->finish($request, 'executed', 'Reminder dihapus.'); }

    private function events(User $user, array $data): Collection
    {
        return CalendarEvent::query()->where('user_id', $user->id)
            ->when($data['target_event_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($data['target_event_ids'] ?? $data['event_ids'] ?? null, fn ($q, $ids) => $q->whereKey($ids))
            ->when($data['title'] ?? null, fn ($q, $title) => $q->where('title','ilike','%'.$title.'%'))
            ->when($data['search_query'] ?? null, fn ($q, $term) => $q->where(fn ($x) => $x->where('title','ilike','%'.$term.'%')->orWhere('description','ilike','%'.$term.'%')->orWhere('location','ilike','%'.$term.'%')->orWhere('category','ilike','%'.$term.'%')))
            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('starts_at','>=',$date))
            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('starts_at','<=',$date))
            ->when($data['scheduled_date'] ?? null, fn ($q, $date) => $q->whereDate('starts_at',$date))
            ->when($data['scheduled_dates'] ?? null, fn ($q, $dates) => $q->where(fn ($x) => collect($dates)->each(fn ($date) => $x->orWhereDate('starts_at',$date))))
            ->orderBy('starts_at')->get();
    }

    private function payload(array $data, ?CalendarEvent $existing = null): array
    {
        $date = $data['scheduled_date'] ?? $existing?->starts_at?->format('Y-m-d') ?? now('Asia/Jakarta')->format('Y-m-d');
        $time = $data['scheduled_time'] ?? $existing?->starts_at?->format('H:i:s') ?? '09:00:00';
        $fields = ['title','description','location','participants','category','priority','color','recurrence','status','all_day'];
        $out = collect($fields)->filter(fn ($field) => array_key_exists($field, $data))->mapWithKeys(fn ($field) => [$field => $data[$field]])->all();
        $out['starts_at'] = Carbon::parse("$date $time", 'Asia/Jakarta');
        if (array_key_exists('ends_at', $data)) $out['ends_at'] = $data['ends_at'];
        return $out;
    }

    private function reply(array $data, string $fallback): string { return trim((string) ($data['human_response'] ?? '')) ?: $fallback; }
    private function action(PromptRequest $request, string $action, CalendarEvent $event, array $payload): void { PromptAction::query()->create(['prompt_request_id' => $request->id,'action_type' => $action,'target_entity_type' => 'event','target_entity_id' => $event->id,'status' => 'executed','payload' => $payload,'result_payload' => ['event_id' => $event->id]]); }
    private function items(Collection $events): array { return $events->map(fn ($e) => ['id' => $e->id,'title' => $e->title,'starts_at' => $e->starts_at?->toIso8601String()])->all(); }
    private function context(User $user, string $text, string $channel): string { $history = PromptRequest::query()->where('user_id',$user->id)->where('channel',$channel)->latest()->limit(6)->get()->reverse()->map(fn ($p) => 'User: '.$p->raw_text."\nZaid: ".data_get($p->execution_summary,'human_response','')."\nAgenda result: ".json_encode(data_get($p->execution_summary,'items',[])))->implode("\n"); return $history === '' ? $text : "Conversation context:\n$history\nCurrent user message: $text"; }
    private function finish(PromptRequest $request, string $status, string $reply, array $result = []): array { $request->update(['execution_status' => $status, 'execution_summary' => ['human_response' => $reply] + $result]); return ['prompt_request_id' => $request->id,'parse_status' => $request->parse_status,'intent' => $request->intent,'requires_confirmation' => false,'result' => $result,'human_response' => $reply]; }
}
