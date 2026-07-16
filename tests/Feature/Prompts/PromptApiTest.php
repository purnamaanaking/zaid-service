<?php

namespace Tests\Feature\Prompts;

use App\Contracts\Prompt\PromptParser;
use App\Models\Task;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptApiTest extends TestCase
{
    public function test_user_can_view_prompt_history(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'cek jadwal hari ini']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/prompts')
            ->assertOk()
            ->assertJsonPath('data.items.0.text', 'cek jadwal hari ini');
    }

    public function test_chat_returns_task_summary_when_parser_cannot_handle_casual_task_question(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0,
            'parse_status' => 'failed',
            'requires_confirmation' => false,
            'entities' => [],
        ]));
        $user = User::factory()->active()->create();
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Tugas Besar 1',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'scheduled_time' => null,
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'gua ada task ga?'])
            ->assertOk()
            ->assertJsonPath('data.human_response', "Kamu punya 1 task pending:\n1. Tugas Besar 1");
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
