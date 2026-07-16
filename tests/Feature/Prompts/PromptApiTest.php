<?php

namespace Tests\Feature\Prompts;

use App\Contracts\Prompt\PromptParser;
use App\Models\PromptRequest;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptApiTest extends TestCase
{
    public function test_prompt_parser_receives_recent_conversation_context(): void
    {
        $user = User::factory()->active()->create();
        PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => 'app_prompt',
            'raw_text' => 'Buat task laporan',
            'normalized_text' => 'Buat task laporan',
            'parse_status' => 'parsed',
            'execution_status' => 'executed',
            'execution_summary' => ['human_response' => 'Task laporan dibuat.'],
        ]);

        $parser = new class implements PromptParser {
            public string $receivedText = '';

            public function parse(string $text, string $userId, ?array $attachments = null): array
            {
                $this->receivedText = $text;

                return ['intent' => 'DELETE', 'confidence_score' => 1, 'parse_status' => 'parsed', 'requires_confirmation' => false, 'entities' => ['title' => 'laporan']];
            }
        };
        $this->app->bind(PromptParser::class, fn () => $parser);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus ini'])->assertOk();
        $this->assertStringContainsString('Task laporan dibuat.', $parser->receivedText);
        $this->assertStringContainsString('Current user message: hapus ini', $parser->receivedText);
    }

    public function test_user_can_view_prompt_history(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'cek jadwal hari ini']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/prompts')
            ->assertOk()
            ->assertJsonPath('data.items.0.text', 'cek jadwal hari ini');
    }

    public function test_user_can_submit_prompt(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.97,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => 'Laporan Penjualan',
                'scheduled_date' => '2026-05-23',
                'scheduled_time' => '10:00:00',
                'all_day' => false,
                'recurrence' => [
                    'type' => 'weekly',
                    'day_of_week' => 'friday',
                    'interval' => 1,
                ],
                'description' => null,
                'search_query' => null,
            ],
        ]));

        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'Buat task laporan penjualan setiap Jumat jam 10 pagi',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'CREATE')
            ->assertJsonPath('data.result.action', 'create_task');
    }
}
