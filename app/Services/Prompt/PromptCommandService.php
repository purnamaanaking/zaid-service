<?php

namespace App\Services\Prompt;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Services\Documents\DocumentScheduleParser;
use App\Models\PromptAction;
use App\Models\PromptRequest;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromptCommandService
{
    public function __construct(private readonly PromptParser $parser, private readonly DocumentScheduleParser $documentSchedules) {}

    public function process(User $user, string $text, string $channel = 'app_prompt', ?array $attachments = null, ?string $selectedDate = null, ?string $selectedFrom = null, ?string $selectedTo = null): array
    {
        $norm = preg_replace('/\b(bis\s+abaca|bisabaca)\b/i', 'bisa baca', $text);
        $norm = preg_replace('/\b(abaca)\b/i', 'baca', $norm);
        $norm = preg_replace('/\b(ajdwal|jadwla|jadawal)\b/i', 'jadwal', $norm);

        $isCapabilityQuestion = (bool) preg_match(
            '/(?:\b(bisa|bisakah|apakah\s+bisa)\b.*\b(baca|ekstrak|paham|proses|terima)\b.*\b(pdf|dokumen|file|excel|csv)\b|\b(lu|kamu|bot)\s+(bisa|bis)\b.*\b(baca|ekstrak|paham|proses)\b.*\b(pdf|dokumen|file)\b)/i',
            $norm
        );
        if ($isCapabilityQuestion) {
            $reply = 'Bisa banget! Kamu bisa kirim file PDF, Excel (XLSX/XLS), atau CSV jadwal ke sini. Nanti aku bantu baca isinya, tampilkan daftar kegiatannya, dan kamu bisa pilih untuk dicatat ke kalender.';
            $request = PromptRequest::query()->create(['user_id' => $user->id, 'channel' => $channel, 'raw_text' => $text, 'normalized_text' => $norm, 'intent' => 'READ', 'confidence_score' => 1.0, 'parse_status' => 'parsed', 'extracted_entities' => [], 'execution_status' => 'executed', 'execution_summary' => ['human_response' => $reply]]);
            return ['prompt_request_id' => $request->id, 'parse_status' => 'parsed', 'intent' => 'READ', 'confidence_score' => 1.0, 'requires_confirmation' => false, 'result' => null, 'human_response' => $reply];
        }

        $parsed = $this->parser->parse($this->context($user, $text, $channel, $selectedDate, $selectedFrom, $selectedTo), $user->id, $attachments);
        $data = $parsed['entities'] ?? [];
        $documentText = collect($attachments ?? [])->where('type', 'document_text')->pluck('text')->filter()->implode("\n\n");
        if ($documentText !== '') {
            $data['document_text'] = $documentText;
            $data['document_candidates'] = $this->documentSchedules->parse($documentText);
        }

        if (empty($data['document_text'])) {
            $isNumberSelection = (bool) (! preg_match('/\b(tanggal|tgl|jam|pukul|tahun|menit|detik)\b/i', $text)
                && preg_match('/\b(?:nomor|no\.?|ke|pilih|catat|jadwalkan)?\s*(?:yang\s+)?(?:nomor|no\.?|ke)?\s*([0-9]+)\b/i', $text));
            $isDocReference = (bool) preg_match('/\b(pdf\w*|dokumen\w*|file\w*|excel\w*|csv\w*|jadwal\s+tadi|jadwal\s+tersebut)\b/i', $text);
            $isConfirmationWord = (bool) preg_match('/\b(semua|seluruh|semuanya|catat\s+semua|simpan\s+semua)\b/i', $text);
            $isTargetFilter = (bool) preg_match('/(?:terkait|khusus|kategori|yang)\s+[a-z0-9]+/i', $text);

            if ($isNumberSelection || $isDocReference || $isConfirmationWord || $isTargetFilter) {
                $recentDoc = PromptRequest::query()
                    ->where('user_id', $user->id)
                    ->where('channel', $channel)
                    ->whereNotNull('extracted_entities->document_text')
                    ->latest()
                    ->first();
                if ($recentDoc) {
                    $docText = data_get($recentDoc->extracted_entities, 'document_text');
                    $docCandidates = data_get($recentDoc->extracted_entities, 'document_candidates', []);
                    $data['document_text'] = $docText;
                    $data['document_candidates'] = $docCandidates ?: $this->documentSchedules->parse($docText);
                }
            }
        }

        if (! empty($data['document_candidates'])) {
            $matchedIdx = null;
            if (! preg_match('/\b(tanggal|tgl|jam|pukul|tahun|menit|detik)\b/i', $text)
                && preg_match('/\b(?:nomor|no\.?|ke|pilih|catat|jadwalkan)?\s*(?:yang\s+)?(?:nomor|no\.?|ke)?\s*([0-9]+)\b/i', $text, $nm)) {
                $num = (int) $nm[1];
                if ($num >= 1 && $num <= count($data['document_candidates'])) {
                    $matchedIdx = $num - 1;
                }
            }

            if ($matchedIdx !== null && isset($data['document_candidates'][$matchedIdx])) {
                $data['document_candidates'] = [$data['document_candidates'][$matchedIdx]];
            } elseif (preg_match('/(?:terkait|khusus\s+untuk|khusus|tentang|kategori)\s+([a-z0-9\s\/&-]+)/i', $text, $m)) {
                $term = trim(preg_replace('/\b(aja|saja|bro|pak|dong|ya|dulu|ini|itu|semua|seluruh)\b/i', '', $m[1]));
                if ($term !== '') {
                    $filtered = collect($data['document_candidates'])
                        ->filter(fn ($c) => str_contains(mb_strtolower($c['searchable'] ?? $c['title']), mb_strtolower($term)))
                        ->values()
                        ->all();
                    if (! empty($filtered)) {
                        $data['document_candidates'] = $filtered;
                    }
                }
            }
        }

        // Safety guard: if intent/action is CREATE but user only asked to check/view, force to READ
        if (in_array(strtoupper($data['action'] ?? $parsed['intent'] ?? ''), ['CREATE_EVENTS', 'CREATE'], true)) {
            if (preg_match('/\b(cek|lihat|baca|periksa|ada apa|apa aja|list|tampilkan)\b/i', $text) && ! preg_match('/\b(catat|buat|buatkan|tambah|tambahkan|jadwalkan|masukkan)\b/i', $text)) {
                $data['action'] = 'LIST_EVENTS';
                $parsed['intent'] = 'READ';
                if (preg_match('/\b(sudah|telah)\s+(saya\s+)?(tambahkan|masukkan|catat|buat)/i', (string) ($data['human_response'] ?? ''))) {
                    $data['human_response'] = null;
                }
            }
        }

        if (in_array(strtoupper($data['action'] ?? $parsed['intent'] ?? ''), ['LIST_EVENTS', 'READ'], true) && ! empty($data['document_candidates']) && empty($data['human_response'])) {
            $listItems = collect($data['document_candidates'])->map(fn ($c, $i) => ($i + 1).'. '.$c['title'].' · '.$c['scheduled_date'].' ('.substr($c['scheduled_time'], 0, 5).'-'.substr($c['scheduled_end_time'], 0, 5).')'.(! empty($c['category']) ? ' ['.$c['category'].']' : ''))->implode("\n");
            $data['human_response'] = "Daftar jadwal di dokumen (".count($data['document_candidates'])." kegiatan):\n\n".$listItems."\n\nMau catat semua jadwal, atau khusus kegiatan tertentu?";
        }

        // If multiple candidates detected from document for creation, require confirmation first unless user said "semua"
        $isExplicitAll = (bool) preg_match('/\b(semua|seluruh|semuanya)\b/i', $text);
        if (! $isExplicitAll && ! empty($data['document_candidates']) && count($data['document_candidates']) > 1 && in_array(strtoupper($data['action'] ?? $parsed['intent'] ?? ''), ['CREATE_EVENTS', 'CREATE'], true)) {
            $question = $this->clarificationQuestion($data);
            $request = PromptRequest::query()->create(['user_id' => $user->id, 'channel' => $channel, 'raw_text' => $text, 'normalized_text' => $text, 'intent' => 'CREATE', 'confidence_score' => 0.95, 'parse_status' => 'ambiguous', 'extracted_entities' => $data, 'execution_status' => 'awaiting_confirmation', 'execution_summary' => ['human_response' => $question]]);
            return ['prompt_request_id' => $request->id, 'parse_status' => 'ambiguous', 'intent' => 'CREATE', 'confidence_score' => 0.95, 'requires_confirmation' => true, 'confirmation' => ['question' => $question, 'entities' => $data], 'result' => null, 'human_response' => $question];
        }
        if ($selectedFrom && $selectedTo) {
            $dates = collect(Carbon::parse($selectedFrom, 'Asia/Jakarta')->toPeriod($selectedTo))->map->format('Y-m-d')->all();
            $data['from'] = $selectedFrom;
            $data['to'] = $selectedTo;
            $data['scheduled_dates'] = $dates;
            $data['scheduled_date'] = $selectedFrom;
        }
        $request = PromptRequest::query()->create(['user_id' => $user->id, 'channel' => $channel, 'raw_text' => $text, 'normalized_text' => $text, 'intent' => $parsed['intent'] ?? null, 'confidence_score' => $parsed['confidence_score'] ?? 0, 'parse_status' => $parsed['parse_status'] ?? 'failed', 'extracted_entities' => $data, 'execution_status' => 'pending']);
        if (($parsed['parse_status'] ?? '') !== 'parsed') {
            $question = $this->clarificationQuestion($data);
            $request->update(['execution_status' => 'awaiting_confirmation']);
            return ['prompt_request_id' => $request->id, 'parse_status' => 'ambiguous', 'intent' => $request->intent, 'confidence_score' => $parsed['confidence_score'] ?? 0, 'requires_confirmation' => true, 'confirmation' => ['question' => $question, 'entities' => $data], 'result' => null, 'human_response' => $question];
        }
        if (! empty($data['recurrence']) && (empty($data['range_start']) || empty($data['range_end']))) {
            $request->update(['execution_status' => 'awaiting_confirmation']);
            return ['prompt_request_id' => $request->id, 'parse_status' => 'ambiguous', 'intent' => $request->intent, 'confidence_score' => $parsed['confidence_score'] ?? 0, 'requires_confirmation' => true, 'confirmation' => ['question' => 'Jadwal berulang butuh tanggal mulai dan tanggal selesai.', 'entities' => $data], 'result' => null, 'human_response' => 'Jadwal berulang butuh tanggal mulai dan tanggal selesai.'];
        }

        $action = strtoupper($data['action'] ?? $parsed['intent'] ?? 'LIST_EVENTS');
        $confidence = (float) ($parsed['confidence_score'] ?? 0);
        if (($parsed['requires_confirmation'] ?? false) || ($confidence < .7 && ! in_array($action, ['RECAP', 'COUNT_EVENTS', 'SEARCH_EVENTS', 'GET_EVENT_LINK', 'CHECK_CONFLICTS', 'CHECK_AVAILABILITY', 'FIND_FREE_SLOT', 'LIST_EVENTS', 'READ'], true))) {
            $request->update(['execution_status' => 'awaiting_confirmation', 'execution_summary' => ['command' => $data, 'reason' => $confidence < .7 ? 'low_confidence' : 'ai_requested_confirmation']]);
            return ['prompt_request_id' => $request->id, 'parse_status' => 'ambiguous', 'intent' => $request->intent, 'confidence_score' => $confidence, 'requires_confirmation' => true, 'confirmation' => ['question' => $data['human_response'] ?? 'Aku kurang yakin. Lanjutkan aksi ini?', 'entities' => $data], 'result' => null, 'human_response' => $data['human_response'] ?? 'Aku kurang yakin. Lanjutkan aksi ini?'];
        }
        return $this->execute($request, $user, $action, $data);
    }

    public function confirm(PromptRequest $request, User $user, bool $confirmed): array
    {
        if (! $confirmed) return $this->finish($request, 'rejected', 'Dibatalkan.');
        $data = $request->extracted_entities ?? [];
        return $this->execute($request, $user, strtoupper($data['action'] ?? $request->intent ?? 'LIST_EVENTS'), $data);
    }

    private function execute(PromptRequest $request, User $user, string $action, array $data): array
    {
        return DB::transaction(fn () => match ($action) {
            'CREATE', 'CREATE_EVENTS' => $this->create($request, $user, $data),
            'UPDATE', 'UPDATE_EVENTS', 'RESCHEDULE_EVENTS' => $this->update($request, $user, $data),
            'DELETE', 'DELETE_EVENTS' => $this->delete($request, $user, $data),
            'SET_REMINDER', 'UPDATE_REMINDER' => $this->reminder($request, $user, $data),
            'DELETE_REMINDER' => $this->removeReminder($request, $user, $data),
            'RECAP', 'COUNT_EVENTS', 'SEARCH_EVENTS', 'GET_EVENT_LINK', 'CHECK_CONFLICTS', 'CHECK_AVAILABILITY', 'FIND_FREE_SLOT', 'LIST_EVENTS', 'READ' => $this->read($request, $user, $data),
            default => $this->finish($request, 'failed', $data['human_response'] ?? 'Perintah agenda belum dikenali.'),
        });
    }

    private function read(PromptRequest $request, User $user, array $data): array
    {
        if (! empty($data['document_text']) && filled($data['human_response'] ?? null)) {
            return $this->finish($request, 'executed', $data['human_response']);
        }

        if (! in_array(strtoupper($data['action'] ?? ''), ['SEARCH_EVENTS', 'GET_EVENT_LINK'], true)) unset($data['search_query']);
        $events = $this->events($user, $data);
        $items = $this->items($events);
        $isLinkRequest = strtoupper($data['action'] ?? '') === 'GET_EVENT_LINK';
        $linkPattern = '/(?:https?:\/\/)?(?:[a-z0-9-]+\.)+[a-z]{2,}(?:\/[^\s]*)?/i';
        $linkEvent = $isLinkRequest ? $events->first(fn (CalendarEvent $event) => preg_match($linkPattern, (string) $event->description)) : null;
        preg_match($linkPattern, (string) $linkEvent?->description, $matches);
        $link = isset($matches[0]) ? (str_starts_with($matches[0], 'http') ? $matches[0] : 'https://'.$matches[0]) : null;
        $fallback = $link ? 'Link Zoom untuk '.$linkEvent->title.' adalah '.$link : ($isLinkRequest && $events->isNotEmpty() ? 'Link Zoom untuk '.$events->first()->title.' belum tersedia dalam data saya.' : ($events->isEmpty() ? 'Belum ada agenda.' : 'Agenda kamu:'));
        $reply = $isLinkRequest ? $fallback : $this->reply($data, $fallback);
        if (! $events->isEmpty() && ! $isLinkRequest) {
            $list = $events->values()->map(fn ($event, $index) => ($index + 1).'. '.$event->title.' · '.$event->starts_at->locale('id')->translatedFormat('l, d M Y').' · '.$event->starts_at->format('H:i').($event->ends_at ? '-'.$event->ends_at->format('H:i') : ''))->implode("\n");
            if (in_array(strtoupper($data['action'] ?? ''), ['LIST_EVENTS', 'READ', 'SEARCH_EVENTS', 'RECAP'], true)) $reply = 'Agenda kamu:';
            $reply .= "\n\n".$list;
        } elseif ($events->isEmpty() && empty($data['human_response'])) {
            $reply = 'Belum ada agenda untuk rentang tersebut.';
        }
        if (in_array(strtoupper($data['action'] ?? ''), ['CHECK_AVAILABILITY', 'CHECK_CONFLICTS', 'COUNT_EVENTS'], true)) $reply .= "\n\n".$events->count().' jadwal ditemukan.';
        return $this->finish($request, 'executed', $reply, ['items' => $items]);
    }

    private function create(PromptRequest $request, User $user, array $data): array
    {
        $candidates = $data['document_candidates'] ?? [];
        if (empty($data['description']) && preg_match('/(?:https?:\/\/)?(?:[a-z0-9-]+\.)+[a-z]{2,}(?:\/[^\s]*)?/i', (string) ($data['human_response'] ?? ''), $matches)) $data['description'] = 'Agenda: '.($data['title'] ?? 'jadwal').'. '.rtrim(str_starts_with($matches[0], 'http') ? $matches[0] : 'https://'.$matches[0], '.,;:');
        $dates = ! empty($data['recurrence']) ? $this->recurrenceDates($data) : ($data['scheduled_dates'] ?? [$data['scheduled_date'] ?? now('Asia/Jakarta')->format('Y-m-d')]);
        $events = $candidates ? collect($candidates)->map(fn ($candidate) => CalendarEvent::query()->create(['user_id' => $user->id] + $this->payload($candidate))) : collect($dates)->map(function ($date) use ($user, $data) { $data['scheduled_date'] = $date; return CalendarEvent::query()->create(['user_id' => $user->id] + $this->payload($data)); });
        $events->each(fn ($event) => $this->action($request, 'create', $event, $data));
        $firstTitle = $events->first()?->title ?? ($data['title'] ?? 'Jadwal');
        $fallback = $events->count() === 1 ? $firstTitle.' sudah masuk agenda.' : $events->count().' jadwal "'.$firstTitle.'" sudah masuk agenda.';
        return $this->finish($request, 'executed', $this->reply($data, $fallback), ['items' => $this->items($events)]);
    }

    private function clarificationQuestion(array $data): string
    {
        if (! empty($data['document_candidates'])) {
            $items = collect($data['document_candidates'])->take(10)->map(fn ($item, $index) => ($index + 1).'. '.$item['title']."\n".$item['scheduled_date'].' · '.substr($item['scheduled_time'], 0, 5).'-'.substr($item['scheduled_end_time'], 0, 5).($item['location'] ? ' · '.$item['location'] : ''))->implode("\n");
            return 'Saya menemukan '.count($data['document_candidates'])." jadwal dari dokumen:\n".$items."\n\nMau buat semua jadwal, atau khusus nama siapa?";
        }
        if (! empty($data['document_text'])) return 'Saya menemukan dokumen, tapi belum menemukan baris jadwal yang lengkap. Mau cek bagian tertentu?';
        if (! empty($data['human_response'])) return $data['human_response'];
        $fields = collect($data['clarification_fields'] ?? []);
        $needsDate = $fields->contains(fn ($field) => in_array($field, ['date', 'year'], true));
        $needsTime = $fields->contains('time');
        if ($needsDate && $needsTime) {
            return ! empty($data['title'])
                ? "Mau dijadwalkan hari apa dan jam berapa untuk {$data['title']} minggu depan?"
                : 'Mau dijadwalkan hari apa, tanggal berapa, dan jam berapa?';
        }
        if ($needsDate) return 'Mau dijadwalkan tanggal berapa? Sertakan bulan dan tahun ya.';
        if ($needsTime) return 'Mau dijadwalkan jam berapa?';
        return 'Bagian mana yang mau dijadwalkan? Sebutkan hari atau tanggal dan jamnya.';
    }

    private function recurrenceDates(array $data): Collection
    {
        $start = Carbon::parse($data['range_start'], 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($data['range_end'], 'Asia/Jakarta')->startOfDay();
        if ($end->lt($start)) return collect();
        $recurrence = $data['recurrence'];
        $weekday = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6][$recurrence['day_of_week'] ?? ''] ?? null;
        $day = (int) ($recurrence['day_of_month'] ?? $start->day);
        $dates = collect();
        for ($date = $start->copy(); $date->lte($end) && $dates->count() < 366; $date->addDay()) {
            if ($recurrence['type'] === 'daily' || ($recurrence['type'] === 'weekly' && $date->dayOfWeek === $weekday) || ($recurrence['type'] === 'monthly' && $date->day === $day)) $dates->push($date->format('Y-m-d'));
        }
        return $dates;
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

        $title = trim((string) ($out['title'] ?? $data['title'] ?? $data['name'] ?? $data['event_name'] ?? $data['agenda'] ?? ''));
        if ($title === '') {
            $desc = trim((string) ($out['description'] ?? $data['description'] ?? ''));
            if ($desc !== '') {
                $cleanDesc = preg_replace('/^(?:jadwal\s+(?:kegiatan|terkait|)\s*)/i', '', $desc);
                $title = \Illuminate\Support\Str::limit($cleanDesc ?: $desc, 80, '');
            } else {
                $title = $existing?->title ?? 'Agenda';
            }
        }
        $out['title'] = $title;

        $out['description'] = filled($out['description'] ?? null) ? $out['description'] : 'Agenda: '.$title.'.';
        $out['starts_at'] = Carbon::parse("$date $time", 'Asia/Jakarta');
        if (array_key_exists('scheduled_end_time', $data) && filled($data['scheduled_end_time'])) {
            $out['ends_at'] = Carbon::parse("$date {$data['scheduled_end_time']}", 'Asia/Jakarta');
        } elseif (array_key_exists('ends_at', $data) && filled($data['ends_at'])) {
            $out['ends_at'] = Carbon::parse($data['ends_at'], 'Asia/Jakarta');
        }
        if (isset($out['ends_at']) && $out['ends_at'] instanceof Carbon && $out['ends_at']->lte($out['starts_at'])) {
            $out['ends_at'] = $out['starts_at']->copy()->addHour();
        }
        return $out;
    }

    private function reply(array $data, string $fallback): string { return trim((string) ($data['human_response'] ?? '')) ?: $fallback; }
    private function action(PromptRequest $request, string $action, CalendarEvent $event, array $payload): void { PromptAction::query()->create(['prompt_request_id' => $request->id,'action_type' => $action,'target_entity_type' => 'event','target_entity_id' => $event->id,'status' => 'executed','payload' => $payload,'result_payload' => ['event_id' => $event->id]]); }
    private function items(Collection $events): array { return $events->map(fn ($e) => ['id' => $e->id,'title' => $e->title,'starts_at' => $e->starts_at?->toIso8601String()])->all(); }
    private function context(User $user, string $text, string $channel, ?string $selectedDate, ?string $selectedFrom = null, ?string $selectedTo = null): string
    {
        $history = PromptRequest::query()->where('user_id', $user->id)->where('channel', $channel)->latest()->limit(6)->get()->reverse()->map(fn ($p) => 'User: '.$p->raw_text."\nZaid: ".data_get($p->execution_summary, 'human_response', '')."\nAgenda result: ".json_encode(data_get($p->execution_summary, 'items', [])).($p->execution_status === 'awaiting_confirmation' ? "\nPending clarification command: ".json_encode($p->extracted_entities).(! empty(data_get($p->extracted_entities, 'document_text')) ? "\nPending document import: ".data_get($p->extracted_entities, 'document_text') : '') : ''))->implode("\n");
        $recentDoc = PromptRequest::query()
            ->where('user_id', $user->id)
            ->where('channel', $channel)
            ->whereNotNull('extracted_entities->document_text')
            ->latest()
            ->first();
        $recentDocText = data_get($recentDoc?->extracted_entities, 'document_text');
        $docContext = ! empty($recentDocText) && ! str_contains($history, 'Pending document import:') ? "\nActive document text in conversation:\n".\Illuminate\Support\Str::limit($recentDocText, 15000) : '';
        $events = $this->items(CalendarEvent::query()->where('user_id', $user->id)->orderBy('starts_at')->limit(100)->get());
        return "Current time: ".now('Asia/Jakarta')->toIso8601String()."\nTimezone: Asia/Jakarta\nSelected date: ".($selectedDate ?? 'none')."\nSelected date range: ".($selectedFrom && $selectedTo ? "$selectedFrom to $selectedTo" : 'none')."\nVisible calendar: month\nEvents: ".json_encode($events)."{$docContext}\nRecent chat:\n{$history}\nCurrent user message: {$text}";
    }
    private function finish(PromptRequest $request, string $status, string $reply, array $result = []): array { $request->update(['execution_status' => $status, 'execution_summary' => ['human_response' => $reply, 'command' => $request->extracted_entities, 'executed_at' => now()->toIso8601String()] + $result]); return ['prompt_request_id' => $request->id,'parse_status' => $request->parse_status,'intent' => $request->intent,'requires_confirmation' => false,'result' => $result,'human_response' => $reply]; }
}
