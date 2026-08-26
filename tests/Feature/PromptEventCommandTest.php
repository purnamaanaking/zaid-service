<?php

namespace Tests\Feature;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Services\Documents\DocumentScheduleParser;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptEventCommandTest extends TestCase
{
    public function test_document_schedule_parser_extracts_rows_for_purnama_filter(): void
    {
        $rows = app(DocumentScheduleParser::class)->parse("No | Nama Mahasiswa | Nama Penguji 1 | Tanggal | Jam | Ruangan\n1 | Ahmad | Purnama Anaking | 27-Jul-2026 | 08.00 - 09.30 | KTT2.05");

        $this->assertSame('Sidang TA: Ahmad', $rows[0]['title']);
        $this->assertSame('2026-07-27', $rows[0]['scheduled_date']);
        $this->assertSame('08:00:00', $rows[0]['scheduled_time']);
        $this->assertSame('09:30:00', $rows[0]['scheduled_end_time']);
        $this->assertStringContainsString('Purnama Anaking', $rows[0]['searchable']);
    }

    public function test_contextual_month_question_returns_ai_answer(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => ['human_response' => 'Itu bulan Juli 2026.'],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'itu bulan apa?'])
            ->assertOk()
            ->assertJsonPath('data.human_response', 'Itu bulan Juli 2026.');
    }

    public function test_parser_receives_recent_agenda_context_for_follow_up(): void
    {
        $parser = new class implements PromptParser {
            public string $received = '';
            public function parse(string $text, string $userId, ?array $attachments = null): array
            {
                $this->received = $text;
                return ['intent' => 'READ', 'confidence_score' => 0.98, 'parse_status' => 'parsed', 'requires_confirmation' => false, 'entities' => ['scheduled_date' => '2026-07-01']];
            }
        };
        $this->app->bind(PromptParser::class, fn () => $parser);
        $user = User::factory()->active()->create();
        \App\Models\PromptRequest::query()->create(['user_id' => $user->id, 'channel' => 'app_prompt', 'raw_text' => 'kalo tanggal 1 juli', 'normalized_text' => 'kalo tanggal 1 juli', 'parse_status' => 'parsed', 'execution_status' => 'executed', 'execution_summary' => ['human_response' => 'Agenda kamu: jogging pagi', 'items' => [['id' => 'agenda-event-id', 'title' => 'Jogging pagi', 'starts_at' => '2026-07-01T06:00:00+07:00']]]]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus 1 aja'])->assertOk();

        $this->assertStringContainsString('User: kalo tanggal 1 juli', $parser->received);
        $this->assertStringContainsString('Zaid: Agenda kamu: jogging pagi', $parser->received);
        $this->assertStringContainsString('Agenda result:', $parser->received);
        $this->assertStringContainsString('Current user message: hapus 1 aja', $parser->received);
    }

    public function test_ambiguous_schedule_returns_a_specific_question(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.9,
            'parse_status' => 'ambiguous',
            'requires_confirmation' => true,
            'entities' => ['title' => 'Kolam renang', 'clarification_fields' => ['date', 'time']],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'buatkan jadwal hari Senin ke kolam renang'])
            ->assertOk()
            ->assertJsonPath('data.human_response', 'Mau dijadwalkan hari Senin tanggal berapa dan jam berapa?');
    }

    public function test_parser_receives_pending_clarification_command_for_text_follow_up(): void
    {
        $parser = new class implements PromptParser {
            public string $received = '';
            public function parse(string $text, string $userId, ?array $attachments = null): array
            {
                $this->received = $text;
                return ['intent' => 'READ', 'confidence_score' => .98, 'parse_status' => 'parsed', 'requires_confirmation' => false, 'entities' => []];
            }
        };
        $this->app->bind(PromptParser::class, fn () => $parser);
        $user = User::factory()->active()->create();
        \App\Models\PromptRequest::query()->create(['user_id' => $user->id, 'channel' => 'app_prompt', 'raw_text' => 'buatkan jadwal renang mulai Senin', 'normalized_text' => 'buatkan jadwal renang mulai Senin', 'parse_status' => 'ambiguous', 'execution_status' => 'awaiting_confirmation', 'extracted_entities' => ['action' => 'CREATE_EVENTS', 'title' => 'Renang', 'recurrence' => ['type' => 'weekly', 'day_of_week' => 'monday']]]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'mulai 1 September 2026 sampai 30 September 2026 jam 9'])
            ->assertOk();

        $this->assertStringContainsString('Pending clarification command:', $parser->received);
        $this->assertStringContainsString('"title":"Renang"', $parser->received);
    }

    public function test_document_with_multiple_schedule_rows_asks_all_or_specific_name(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => .98,
            'parse_status' => 'ambiguous',
            'requires_confirmation' => true,
            'entities' => ['action' => 'CREATE_EVENTS', 'human_response' => 'Bagian mana yang mau dijadwalkan?'],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'Ekstrak jadwal dari file sidang.xlsx',
            'attachments' => [['type' => 'document_text', 'url' => null, 'name' => 'sidang.xlsx', 'text' => "Nama Mahasiswa | Nama Penguji 1 | Tanggal | Jam | Ruangan\nAhmad | Purnama Anaking | 27-Jul-2026 | 08.00 - 09.30 | KTT2.05"]],
        ])->assertOk()
            ->assertJsonPath('data.human_response', "Saya menemukan 1 jadwal dari dokumen:\n1. Sidang TA: Ahmad\n2026-07-27 · 08:00-09:30 · KTT2.05\n\nMau buat semua jadwal, atau khusus nama siapa?");
    }

    public function test_parser_receives_pending_document_text_for_a_filter_follow_up(): void
    {
        $parser = new class implements PromptParser {
            public string $received = '';
            public function parse(string $text, string $userId, ?array $attachments = null): array
            {
                $this->received = $text;
                return ['intent' => 'READ', 'confidence_score' => .98, 'parse_status' => 'parsed', 'requires_confirmation' => false, 'entities' => []];
            }
        };
        $this->app->bind(PromptParser::class, fn () => $parser);
        $user = User::factory()->active()->create();
        \App\Models\PromptRequest::query()->create(['user_id' => $user->id, 'channel' => 'app_prompt', 'raw_text' => 'Ekstrak jadwal sidang', 'normalized_text' => 'Ekstrak jadwal sidang', 'parse_status' => 'ambiguous', 'execution_status' => 'awaiting_confirmation', 'extracted_entities' => ['action' => 'CREATE_EVENTS', 'document_text' => 'Nama Mahasiswa | Purnama Anaking | Senin | 27-Jul-2026 | 08.00 - 09.30']]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'khusus untuk Purnama Anaking'])
            ->assertOk();

        $this->assertStringContainsString('Pending document import:', $parser->received);
        $this->assertStringContainsString('Purnama Anaking', $parser->received);
    }

    public function test_document_filter_follow_up_creates_only_matching_rows(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE', 'confidence_score' => .98, 'parse_status' => 'parsed', 'requires_confirmation' => false, 'entities' => ['action' => 'CREATE_EVENTS'],
        ]));
        $user = User::factory()->active()->create();
        \App\Models\PromptRequest::query()->create(['user_id' => $user->id, 'channel' => 'app_prompt', 'raw_text' => 'file sidang', 'normalized_text' => 'file sidang', 'parse_status' => 'ambiguous', 'execution_status' => 'awaiting_confirmation', 'extracted_entities' => ['document_text' => 'source', 'document_candidates' => [
            ['title' => 'Sidang TA: Ahmad', 'description' => 'Penguji: Purnama Anaking', 'scheduled_date' => '2026-07-27', 'scheduled_time' => '08:00:00', 'scheduled_end_time' => '09:30:00', 'location' => 'KTT2.05', 'searchable' => 'Ahmad | Purnama Anaking'],
            ['title' => 'Sidang TA: Budi', 'description' => 'Penguji: Lain', 'scheduled_date' => '2026-07-27', 'scheduled_time' => '10:00:00', 'scheduled_end_time' => '11:30:00', 'location' => 'KTT2.06', 'searchable' => 'Budi | Lain'],
        ]]]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'khusus untuk Purnama Anaking'])->assertOk();

        $this->assertDatabaseCount('calendar_events', 1);
        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'title' => 'Sidang TA: Ahmad', 'starts_at' => '2026-07-27 08:00:00', 'ends_at' => '2026-07-27 09:30:00']);
    }

    public function test_create_prompt_makes_events_for_each_explicit_date(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => ['title' => 'Jogging pagi', 'scheduled_dates' => ['2026-07-01', '2026-07-02', '2026-07-03', '2026-07-04'], 'scheduled_time' => '06:00:00'],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'buat jadwal dari tanggal 1 sampai 4 juli jogging pagi jam 6'])
            ->assertOk()
            ->assertJsonPath('data.human_response', '4 jadwal "Jogging pagi" sudah masuk agenda.');

        $this->assertDatabaseCount('calendar_events', 4);
    }

    public function test_create_prompt_preserves_explicit_start_and_end_times(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => ['title' => 'Latihan CTF', 'scheduled_date' => '2026-08-15', 'scheduled_time' => '08:00:00', 'scheduled_end_time' => '15:00:00'],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'buatkan latihan CTF besok jam 8 sampai jam 3 sore'])->assertOk();

        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'title' => 'Latihan CTF', 'starts_at' => '2026-08-15 08:00:00', 'ends_at' => '2026-08-15 15:00:00']);
    }

    public function test_delete_prompt_extracts_each_date_when_parser_omits_dates(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => ['title' => null],
        ]));
        $user = User::factory()->active()->create();
        $first = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'A', 'starts_at' => '2026-07-16 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $second = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'B', 'starts_at' => '2026-07-17 09:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus jadwal tanggal 16 dan 17 juli'])
            ->assertOk()
            ->assertJsonPath('data.human_response', '2 jadwal sudah dihapus.');

        $this->assertSoftDeleted('calendar_events', ['id' => $first->id]);
        $this->assertSoftDeleted('calendar_events', ['id' => $second->id]);
    }

    public function test_delete_prompt_does_not_remove_multiple_events_on_same_date(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => ['title' => null, 'scheduled_date' => '2026-07-17'],
        ]));
        $user = User::factory()->active()->create();
        $first = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting pagi', 'starts_at' => '2026-07-17 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $second = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting sore', 'starts_at' => '2026-07-17 15:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus jadwal tanggal 17 juli'])
            ->assertOk()
            ->assertJsonPath('data.human_response', '2 jadwal sudah dihapus.');

        $this->assertSoftDeleted('calendar_events', ['id' => $first->id]);
        $this->assertSoftDeleted('calendar_events', ['id' => $second->id]);
    }

    public function test_delete_prompt_targets_event_title_and_date(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'title' => 'Meeting',
                'scheduled_date' => '2026-07-07',
            ],
        ]));
        $user = User::factory()->active()->create();
        $target = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-07 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $other = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-21 09:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus jadwal meeting tanggal 7 juli'])
            ->assertOk()
            ->assertJsonPath('data.human_response', '1 jadwal sudah dihapus.');

        $this->assertSoftDeleted('calendar_events', ['id' => $target->id]);
        $this->assertDatabaseHas('calendar_events', ['id' => $other->id, 'deleted_at' => null]);
    }
}
