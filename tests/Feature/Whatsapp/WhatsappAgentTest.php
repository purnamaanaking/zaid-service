<?php

namespace Tests\Feature\Whatsapp;

use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappAgentTest extends TestCase
{
    private User $user;

    private string $phone = '+6281556796250';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $this->user->id,
            'phone_e164' => $this->phone,
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        config(['services.whatsapp.driver' => 'waha']);
    }

    private function fakeAiResponse(string $reply, ?array $action = null): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['reply' => $reply, 'action' => $action])]]],
            ]),
            '*' => Http::response([], 200),
        ]);
    }

    private function sendWhatsApp(string $text, string $waId = 'wamid-test'): void
    {
        app(\App\Services\Whatsapp\WhatsappWebhookService::class)->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => $waId,
                'from' => ltrim($this->phone, '+') . '@c.us',
                'to' => '6285182302209@c.us',
                'body' => $text,
                'fromMe' => false,
            ],
        ]);
    }

    private function getReplyText(string $waId = 'wamid-test'): string
    {
        return WhatsappMessage::query()
            ->where('wa_message_id', $waId . '_reply')
            ->firstOrFail()
            ->message_text;
    }

    public function test_greeting_gets_casual_reply(): void
    {
        $this->fakeAiResponse('Yo, ada apa nih? Mau cek jadwal atau ada yang perlu diatur?');

        $this->sendWhatsApp('Bro');

        $reply = $this->getReplyText();
        $this->assertStringContainsString('Yo', $reply);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'chat/completions')) {
                return false;
            }
            $messages = $request['messages'] ?? [];
            $system = $messages[0]['content'] ?? '';

            return str_contains($system, 'Zaid') && str_contains($system, 'KONTEKS SAAT INI');
        });
    }

    public function test_numbered_delete_removes_today_event_without_calling_ai(): void
    {
        $event = CalendarEvent::query()->create([
            'user_id' => $this->user->id,
            'title' => 'Meeting tim',
            'starts_at' => now()->setTime(9, 0),
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
        ]);
        Http::fake(['*' => Http::response([], 400)]);

        $this->sendWhatsApp('hapus no 1', 'wamid-numbered-delete');

        $this->assertSoftDeleted('calendar_events', ['id' => $event->id]);
        $this->assertStringContainsString('Meeting tim', $this->getReplyText('wamid-numbered-delete'));
    }

    public function test_agent_executes_every_action_in_a_multi_command_message(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'reply' => 'Siap, dua-duanya sudah dicatat.',
                    'actions' => [
                        [
                            'type' => 'create',
                            'task_id' => null,
                            'data' => [
                                'entity_type' => 'task',
                                'title' => 'Tugas Besar 1',
                                'scheduled_date' => now()->addDay()->format('Y-m-d'),
                                'scheduled_time' => null,
                                'all_day' => true,
                            ],
                        ],
                        [
                            'type' => 'create',
                            'task_id' => null,
                            'data' => [
                                'entity_type' => 'event',
                                'title' => 'Meeting',
                                'description' => 'Lokasi: Aruna',
                                'scheduled_date' => now()->format('Y-m-d'),
                                'scheduled_time' => '16:00:00',
                                'all_day' => false,
                            ],
                        ],
                    ],
                ])]]],
            ]),
            '*' => Http::response([], 200),
        ]);

        $this->sendWhatsApp("Buat task tugas besar 1 deadline besok\nBuat jadwal meeting jam 4 sore di Aruna", 'wamid-multi-command');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Tugas Besar 1',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $this->user->id,
            'title' => 'Meeting',
        ]);
    }

    public function test_agent_extracts_json_after_model_preamble(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => "Siap, aku catat.\n".json_encode([
                    'reply' => 'Task Benerin Zaid sudah dibuat.',
                    'action' => [
                        'type' => 'create',
                        'task_id' => null,
                        'data' => [
                            'entity_type' => 'task',
                            'title' => 'Benerin Zaid',
                            'scheduled_date' => now()->format('Y-m-d'),
                            'scheduled_time' => null,
                            'all_day' => true,
                        ],
                    ],
                ])]]],
            ]),
            '*' => Http::response([], 200),
        ]);

        $this->sendWhatsApp('buat task benerin zaid malam ini', 'wamid-json-preamble');

        $this->assertDatabaseHas('tasks', ['user_id' => $this->user->id, 'title' => 'Benerin Zaid']);
        $this->assertSame('Task Benerin Zaid sudah dibuat.', $this->getReplyText('wamid-json-preamble'));
    }

    public function test_malformed_ai_response_gets_a_clear_fallback_reply(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '']]],
            ]),
            '*' => Http::response([], 200),
        ]);

        $this->sendWhatsApp('buat task tugas besar besok', 'wamid-malformed-ai');

        $this->assertSame(
            'Pesanmu masuk, tapi aku belum bisa baca perintahnya. Coba pisahkan perintah per baris ya bro.',
            $this->getReplyText('wamid-malformed-ai'),
        );
        $this->assertDatabaseMissing('tasks', ['user_id' => $this->user->id]);
    }

    public function test_agent_retries_with_fallback_model_when_primary_model_fails(): void
    {
        config(['services.openai.model_fallback' => 'deepseek-v4-pro']);
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;

            if ($attempt === 1) {
                return Http::response(['error' => ['message' => 'Upstream failed']], 400);
            }

            $content = json_encode([
                'reply' => 'Task Tubes 2 sudah dibuat.',
                'action' => [
                    'type' => 'create',
                    'task_id' => null,
                    'data' => [
                        'entity_type' => 'task',
                        'title' => 'Tubes 2',
                        'scheduled_date' => now()->format('Y-m-d'),
                        'scheduled_time' => null,
                        'all_day' => true,
                    ],
                ],
            ]);

            return Http::response(['choices' => [['message' => ['content' => $content]]]], 200);
        });

        $this->sendWhatsApp('buat task tubes 2 deadline malam ini', 'wamid-fallback-model');

        $this->assertDatabaseHas('tasks', ['user_id' => $this->user->id, 'title' => 'Tubes 2']);
        Http::assertSentCount(3);
    }

    public function test_agent_creates_task_via_action(): void
    {
        $this->fakeAiResponse('Siap, meeting besok jam 9 udah aku catat ya! 👍', [
            'type' => 'create',
            'task_id' => null,
            'data' => [
                'title' => 'Meeting',
                'scheduled_date' => '2026-05-08',
                'scheduled_time' => '09:00:00',
                'all_day' => false,
            ],
        ]);

        $this->sendWhatsApp('bikin task meeting besok jam 9', 'wamid-create');

        $reply = $this->getReplyText('wamid-create');
        $this->assertStringContainsString('meeting', strtolower($reply));

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Meeting',
            'scheduled_date' => '2026-05-08',
            'scheduled_time' => '09:00:00',
        ]);
    }

    public function test_agent_creates_calendar_event_when_ai_marks_event(): void
    {
        $this->fakeAiResponse('Siap, meeting penelitian jam 07:00 sudah aku catat.', [
            'type' => 'create',
            'task_id' => null,
            'data' => [
                'entity_type' => 'event',
                'title' => 'Meeting penelitian',
                'scheduled_date' => '2026-05-08',
                'scheduled_time' => '07:00:00',
                'all_day' => false,
            ],
        ]);

        $this->sendWhatsApp('buat meeting penelitian jam 7 pagi', 'wamid-event');

        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $this->user->id,
            'title' => 'Meeting penelitian',
            'all_day' => false,
        ]);
        $this->assertDatabaseMissing('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Meeting penelitian',
        ]);
    }

    public function test_explicit_task_wins_when_ai_mislabels_it_as_event(): void
    {
        $this->fakeAiResponse('Siap.', [
            'type' => 'create',
            'task_id' => null,
            'data' => [
                'entity_type' => 'event',
                'title' => 'Follow up dosen',
                'scheduled_date' => '2026-05-08',
                'scheduled_time' => null,
                'all_day' => true,
            ],
        ]);

        $this->sendWhatsApp('buat task follow up dosen', 'wamid-task-over-event');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Follow up dosen',
        ]);
        $this->assertDatabaseMissing('calendar_events', [
            'user_id' => $this->user->id,
            'title' => 'Follow up dosen',
        ]);
    }

    public function test_agent_updates_task_via_action(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Meeting',
            'scheduled_date' => '2026-05-07',
            'scheduled_time' => '21:30:00',
        ]);

        $this->fakeAiResponse('Done, Meeting udah aku pindah ke jam 22:00 ya! 👍', [
            'type' => 'update',
            'task_id' => $task->id,
            'data' => [
                'scheduled_time' => '22:00:00',
            ],
        ]);

        $this->sendWhatsApp('ubah meeting jadi jam 10 malam', 'wamid-update');

        $reply = $this->getReplyText('wamid-update');
        $this->assertStringContainsString('22:00', $reply);

        $task->refresh();
        $this->assertSame('22:00:00', $task->scheduled_time);
    }

    public function test_agent_deletes_task_via_action(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Meeting Batal',
            'scheduled_date' => '2026-05-07',
            'scheduled_time' => '15:00:00',
        ]);

        $this->fakeAiResponse('Oke, Meeting Batal udah aku hapus ya! 🗑️', [
            'type' => 'delete',
            'task_id' => $task->id,
            'data' => [],
        ]);

        $this->sendWhatsApp('hapus meeting batal', 'wamid-delete');

        $reply = $this->getReplyText('wamid-delete');
        $this->assertStringContainsString('hapus', strtolower($reply));

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_quick_schedule_read_includes_calendar_events(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        CalendarEvent::query()->create([
            'user_id' => $this->user->id,
            'title' => 'Benerin kartu ATM',
            'starts_at' => now()->setTime(14, 0),
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
        ]);

        $this->sendWhatsApp('jadwal hari ini?', 'wamid-calendar-event-read');

        $this->assertStringContainsString('Benerin kartu ATM', $this->getReplyText('wamid-calendar-event-read'));
    }

    public function test_quick_read_lists_tasks_and_events_when_both_are_requested(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Kirim laporan',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => null,
        ]);
        CalendarEvent::query()->create([
            'user_id' => $this->user->id,
            'title' => 'Meeting tim',
            'starts_at' => now()->setTime(14, 0),
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
        ]);

        $this->sendWhatsApp('list task dan jadwal hari ini', 'wamid-all-today');

        $reply = $this->getReplyText('wamid-all-today');
        $this->assertStringContainsString('Kirim laporan', $reply);
        $this->assertStringContainsString('Meeting tim', $reply);
    }

    public function test_agent_reads_agenda_via_action(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Gym',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '20:30:00',
        ]);

        $this->fakeAiResponse("Hari ini kamu ada:\n1. 🏋️ Gym - 20:30\n\nMau tambah atau ubah sesuatu?", [
            'type' => 'read',
            'task_id' => null,
            'data' => ['scheduled_date' => now()->format('Y-m-d')],
        ]);

        $this->sendWhatsApp('cek jadwal hari ini', 'wamid-read');

        $reply = $this->getReplyText('wamid-read');
        $this->assertStringContainsString('Gym', $reply);
    }

    public function test_conversation_history_is_included_in_ai_call(): void
    {
        // Create prior conversation
        WhatsappMessage::query()->create([
            'user_id' => $this->user->id,
            'direction' => 'inbound',
            'wa_message_id' => 'prev-in',
            'sender_phone_e164' => $this->phone,
            'recipient_phone_e164' => 'bot',
            'message_text' => 'cek jadwal hari ini',
            'processing_status' => 'executed',
        ]);
        WhatsappMessage::query()->create([
            'user_id' => $this->user->id,
            'direction' => 'outbound',
            'wa_message_id' => 'prev-out',
            'sender_phone_e164' => 'bot',
            'recipient_phone_e164' => $this->phone,
            'message_text' => 'Hari ini kamu ada: Meeting 21:30',
            'processing_status' => 'replied',
        ]);

        $this->fakeAiResponse('Done, Meeting udah aku pindah ke jam 22:00! 👍', [
            'type' => 'update',
            'task_id' => null,
            'data' => [],
        ]);

        $this->sendWhatsApp('ubah yang meeting itu jadi jam 10 malam', 'wamid-ctx');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'chat/completions')) {
                return false;
            }
            $messages = $request['messages'] ?? [];
            $hasHistory = false;
            foreach ($messages as $msg) {
                if (($msg['role'] ?? '') === 'user' && str_contains($msg['content'] ?? '', 'cek jadwal hari ini')) {
                    $hasHistory = true;
                }
            }

            return $hasHistory;
        });
    }

    public function test_task_context_is_included_in_system_prompt(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Gym Session',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '20:30:00',
        ]);

        $this->fakeAiResponse('Hari ini kamu ada Gym jam 20:30.', [
            'type' => 'read',
            'task_id' => null,
            'data' => ['scheduled_date' => now()->format('Y-m-d')],
        ]);

        $this->sendWhatsApp('ada apa hari ini?', 'wamid-ctx2');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'chat/completions')) {
                return false;
            }
            $system = $request['messages'][0]['content'] ?? '';

            return str_contains($system, 'Gym Session') && str_contains($system, '20:30');
        });
    }

    public function test_ai_failure_returns_friendly_error(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response('Internal Server Error', 500),
            '*' => Http::response([], 200),
        ]);

        $this->sendWhatsApp('halo', 'wamid-fail');

        $reply = $this->getReplyText('wamid-fail');
        $this->assertStringContainsString('gangguan', $reply);
    }

    public function test_plain_text_ai_response_is_used_as_reply(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Yo bro, ada yang bisa gue bantu?']]],
            ]),
            '*' => Http::response([], 200),
        ]);

        $this->sendWhatsApp('hey', 'wamid-plain');

        $reply = $this->getReplyText('wamid-plain');
        $this->assertStringContainsString('Yo bro', $reply);
    }

    public function test_schedule_creation_phrase_does_not_take_quick_read_path(): void
    {
        $this->fakeAiResponse('Siap, meeting penelitian jam 07:00 sudah aku buat.', [
            'type' => 'create',
            'task_id' => null,
            'data' => [
                'title' => 'Meeting penelitian',
                'scheduled_date' => now()->format('Y-m-d'),
                'scheduled_time' => '07:00:00',
                'all_day' => false,
            ],
        ]);

        $this->sendWhatsApp('Buat task meeting penelitian hari ini jam 7 pagi', 'wamid-create-schedule');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Meeting penelitian',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '07:00:00',
        ]);
    }

    public function test_quick_read_task_hari_ini_prioritizes_today_items_not_all_pending_tasks(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task Hari Ini',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => null,
            'status' => 'pending',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task Lama',
            'scheduled_date' => '2018-07-06',
            'scheduled_time' => null,
            'status' => 'pending',
        ]);

        Http::fake();

        $this->sendWhatsApp('cek task hari ini', 'wamid-task-today');

        $reply = $this->getReplyText('wamid-task-today');

        $this->assertStringContainsString('hari ini', strtolower($reply));
        $this->assertStringContainsString('Task Hari Ini', $reply);
        $this->assertStringNotContainsString('Task Lama', $reply);
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }

    public function test_quick_read_overdue_returns_only_overdue_pending_tasks(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task Overdue',
            'scheduled_date' => now()->subDay()->format('Y-m-d'),
            'scheduled_time' => null,
            'status' => 'pending',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task Future',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'scheduled_time' => null,
            'status' => 'pending',
        ]);

        Http::fake();

        $this->sendWhatsApp('yang overdue apa?', 'wamid-overdue');

        $reply = $this->getReplyText('wamid-overdue');

        $this->assertStringContainsString('overdue', strtolower($reply));
        $this->assertStringContainsString('Task Overdue', $reply);
        $this->assertStringNotContainsString('Task Future', $reply);
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }

    public function test_quick_read_tasks_overdue_excludes_old_calendar_events(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task Google Tasks Overdue',
            'scheduled_date' => now()->subWeek()->format('Y-m-d'),
            'scheduled_time' => null,
            'status' => 'pending',
            'source_channel' => 'google_tasks',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Old Calendar Event',
            'scheduled_date' => now()->subYears(2)->format('Y-m-d'),
            'scheduled_time' => '10:00:00',
            'status' => 'pending',
            'source_channel' => 'google_calendar',
        ]);

        Http::fake();

        $this->sendWhatsApp('cek tasks overdue', 'wamid-overdue-tasks');

        $reply = $this->getReplyText('wamid-overdue-tasks');

        $this->assertStringContainsString('Task Google Tasks Overdue', $reply);
        $this->assertStringNotContainsString('Old Calendar Event', $reply);
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }

    public function test_quick_delete_pending_tasks_without_time_deletes_only_tasks_not_schedule_items(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task Pending',
            'scheduled_date' => null,
            'scheduled_time' => null,
            'status' => 'pending',
            'source_channel' => 'google_tasks',
        ]);

        $schedule = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Schedule Pending',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '10:00:00',
            'status' => 'pending',
            'source_channel' => 'google_calendar',
        ]);

        Http::fake();

        $this->sendWhatsApp('hapus tasks yang belum selesai', 'wamid-delete-pending-tasks');

        $reply = $this->getReplyText('wamid-delete-pending-tasks');

        $this->assertStringContainsString('udah aku hapus', strtolower($reply));
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('tasks', ['id' => $schedule->id, 'deleted_at' => null]);
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }

    public function test_quick_read_tasks_hari_ini_excludes_schedule_items_with_time(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task No Time',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => null,
            'status' => 'pending',
            'source_channel' => 'google_tasks',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Gym',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '20:00:00',
            'status' => 'pending',
            'source_channel' => 'google_calendar',
        ]);

        Http::fake();

        $this->sendWhatsApp('tasks hari ini', 'wamid-tasks-today-split');

        $reply = $this->getReplyText('wamid-tasks-today-split');

        $this->assertStringContainsString('Task No Time', $reply);
        $this->assertStringNotContainsString('Gym - 20:00', $reply);
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }

    public function test_quick_read_pending_tasks_lists_items_from_multiple_google_task_lists(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Penelitian',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => null,
            'status' => 'pending',
            'source_channel' => 'google_tasks',
            'google_task_list_id' => 'list-my-tasks',
            'google_task_list_title' => 'My Tasks',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'skema api',
            'scheduled_date' => null,
            'scheduled_time' => null,
            'status' => 'pending',
            'source_channel' => 'google_tasks',
            'google_task_list_id' => 'list-kuliah',
            'google_task_list_title' => 'KULIAH',
        ]);

        Http::fake();

        $this->sendWhatsApp('cek tasks apa aja', 'wamid-multi-list-read');

        $reply = $this->getReplyText('wamid-multi-list-read');

        $this->assertStringContainsString('Penelitian', $reply);
        $this->assertStringContainsString('skema api', $reply);
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }
}
