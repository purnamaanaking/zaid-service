<?php

namespace Tests\Feature\Prompts;

use App\Contracts\Prompt\PromptParser;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptApiTest extends TestCase
{
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
