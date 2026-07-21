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
        $parsed = $this->parser->parse($this->context($user, $text, $channel), $user->id, $attachments);
        $entities = $parsed['entities'] ?? [];
        if (($parsed['intent'] ?? null) === 'DELETE' && empty($entities['scheduled_dates'])) {
            preg_match_all('/\b(\d{1,2})\s*(?:dan|,|&)?\s*(\d{1,2})?\s*juli\b/ui', $text, $matches, PREG_SET_ORDER);
            $entities['scheduled_dates'] = collect($matches)->flatMap(fn ($match) => array_filter([$match[1] ?? null, $match[2] ?? null]))->map(fn ($day) => sprintf('%s-07-%02d', now('Asia/Jakarta')->year, $day))->all();
        }
        if ($selectedDate !== null && empty($entities['scheduled_date']) && empty($entities['scheduled_dates'])) $entities['scheduled_date'] = $selectedDate;
        $request = PromptRequest::query()->create(['user_id' => $user->id, 'channel' => $channel, 'raw_text' => $text, 'normalized_text' => $text, 'intent' => $parsed['intent'] ?? null, 'confidence_score' => $parsed['confidence_score'] ?? 0, 'parse_status' => $parsed['parse_status'] ?? 'failed', 'extracted_entities' => $entities, 'execution_status' => 'pending']);
        $intent = $parsed['intent'] ?? 'READ';
        if (($parsed['parse_status'] ?? 'failed') !== 'parsed') return $this->finish($request, 'failed', 'Aku fokus bantu agenda dan event. Coba tulis: "buat meeting besok jam 9".');
        if ($intent === 'READ') {
            $events = CalendarEvent::query()->where('user_id', $user->id)->when($entities['scheduled_date'] ?? null, fn ($q, $date) => $q->whereDate('starts_at', $date))->orderBy('starts_at')->get();
            $reply = $events->isEmpty() ? 'Belum ada agenda.' : "Agenda kamu:\n".$events->map(fn ($event, $i) => ($i + 1).'. '.$event->title.' - '.$event->starts_at->format('Y-m-d H:i'))->implode("\n");
            return $this->finish($request, 'executed', $reply);
        }
        if ($intent === 'CREATE') {
            $dates = $entities['scheduled_dates'] ?? [];
            if (! $dates) $dates = [$entities['scheduled_date'] ?? now('Asia/Jakarta')->format('Y-m-d')];
            $events = collect($dates)->map(function (string $date) use ($user, $entities): CalendarEvent {
                $data = $entities;
                $data['scheduled_date'] = $date;
                return $this->event($user, $data);
            });
            $events->each(fn (CalendarEvent $event) => PromptAction::query()->create(['prompt_request_id' => $request->id, 'action_type' => 'create', 'target_entity_type' => 'event', 'target_entity_id' => $event->id, 'status' => 'executed', 'payload' => $entities, 'result_payload' => ['event_id' => $event->id]]));
            $reply = $events->count() === 1 ? "{$events->first()->title} sudah masuk agenda." : $events->count().' jadwal "'.$events->first()->title.'" sudah masuk agenda.';
            return $this->finish($request, 'executed', $reply, ['event_id' => $events->first()->id]);
        }
        $dates = $entities['scheduled_dates'] ?? [];
        if ($intent === 'DELETE' && count($dates) > 1) {
            $events = CalendarEvent::query()->where('user_id', $user->id)->where(function ($query) use ($dates) {
                foreach ($dates as $date) $query->orWhereDate('starts_at', $date);
            })->get();
            if ($events->isEmpty()) return $this->finish($request, 'failed', 'Event yang dimaksud belum ketemu.');
            $events->each(fn (CalendarEvent $event) => $event->delete());
            return $this->finish($request, 'executed', $events->count().' jadwal sudah dihapus.');
        }
        $events = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->when($entities['title'] ?? null, fn ($query, $title) => $query->where('title', 'ilike', '%'.$title.'%'))
            ->when($entities['scheduled_date'] ?? null, fn ($query, $date) => $query->whereDate('starts_at', $date))
            ->latest('starts_at')
            ->limit(2)
            ->get();
        if ($events->isEmpty()) return $this->finish($request, 'failed', 'Event yang dimaksud belum ketemu.');
        if ($events->count() > 1) return $this->finish($request, 'failed', 'Ada '.$events->count().' jadwal pada tanggal ini. Sebut judul atau jamnya dulu.');
        $event = $events->first();
        if ($intent === 'DELETE') $event->delete(); else $event->update($this->payload($entities));
        PromptAction::query()->create(['prompt_request_id' => $request->id, 'action_type' => strtolower($intent), 'target_entity_type' => 'event', 'target_entity_id' => $event->id, 'status' => 'executed', 'payload' => $entities, 'result_payload' => ['event_id' => $event->id]]);
        return $this->finish($request, 'executed', $intent === 'DELETE' ? 'Event sudah dihapus.' : 'Event sudah diubah.', ['event_id' => $event->id]);
    }

    public function confirm(PromptRequest $request, User $user, bool $confirmed): array { return $this->finish($request, 'executed', $confirmed ? 'Tidak ada aksi yang menunggu konfirmasi.' : 'Dibatalkan.'); }
    private function context(User $user, string $text, string $channel): string
    {
        $history = PromptRequest::query()->where('user_id', $user->id)->where('channel', $channel)->latest()->limit(6)->get()->reverse()->map(fn (PromptRequest $prompt) => 'User: '.$prompt->raw_text."\nZaid: ".data_get($prompt->execution_summary, 'human_response', ''))->implode("\n");
        return $history === '' ? $text : "Conversation context:\n{$history}\n\nCurrent user message: {$text}";
    }
    private function event(User $user, array $entities): CalendarEvent { return CalendarEvent::query()->create(['user_id' => $user->id] + $this->payload($entities)); }
    private function payload(array $e): array { $date = $e['scheduled_date'] ?? now()->format('Y-m-d'); $time = $e['scheduled_time'] ?? '09:00:00'; return ['title' => $e['title'] ?? 'Event baru', 'description' => $e['description'] ?? null, 'starts_at' => Carbon::parse("$date $time", 'Asia/Jakarta'), 'timezone' => 'Asia/Jakarta', 'all_day' => (bool) ($e['all_day'] ?? false)]; }
    private function finish(PromptRequest $request, string $status, string $reply, array $result = []): array { $request->update(['execution_status' => $status, 'execution_summary' => ['human_response' => $reply] + $result]); return ['prompt_request_id' => $request->id, 'parse_status' => $request->parse_status, 'intent' => $request->intent, 'requires_confirmation' => false, 'result' => $result, 'human_response' => $reply]; }
}
