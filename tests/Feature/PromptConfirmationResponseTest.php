<?php

namespace Tests\Feature;

use App\Contracts\Prompt\PromptParser;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptConfirmationResponseTest extends TestCase
{
    public function test_confirmation_includes_entities_for_mobile_preview(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => true,
            'entities' => [
                'action' => 'CREATE_EVENTS',
                'title' => 'Meeting',
                'scheduled_date' => '2026-08-08',
                'scheduled_time' => '09:00:00',
            ],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'buat meeting besok jam 9'])
            ->assertOk()
            ->assertJsonPath('data.requires_confirmation', true)
            ->assertJsonPath('data.confirmation.entities.action', 'CREATE_EVENTS')
            ->assertJsonPath('data.confirmation.entities.title', 'Meeting');
    }
}
