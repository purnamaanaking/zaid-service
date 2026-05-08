<?php

namespace Tests\Feature\Whatsapp;

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

        $this->sendWhatsApp('bikin meeting besok jam 9', 'wamid-create');

        $reply = $this->getReplyText('wamid-create');
        $this->assertStringContainsString('meeting', strtolower($reply));

        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Meeting',
            'scheduled_date' => '2026-05-08',
            'scheduled_time' => '09:00:00',
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
}
