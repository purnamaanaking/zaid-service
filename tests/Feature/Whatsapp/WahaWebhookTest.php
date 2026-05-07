<?php

namespace Tests\Feature\Whatsapp;

use App\Contracts\Prompt\PromptParser;
use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class WahaWebhookTest extends TestCase
{
    public function test_waha_inbound_message_is_processed(): void
    {
        config(['services.whatsapp.driver' => 'waha']);
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser());
        Http::fake();

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+628123456789',
            'phone_local' => '08123456789',
            'country_code' => 'ID',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
            'linked_for_whatsapp_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/webhooks/whatsapp', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'true_628123456789@c.us_ABC123',
                'from' => '628123456789@c.us',
                'fromMe' => false,
                'to' => '628111111111@c.us',
                'body' => 'cek agenda hari ini',
                'hasMedia' => false,
                'source' => 'app',
            ],
        ]);

        $response->assertStatus(202);
    }

    public function test_waha_status_endpoint_requires_auth_and_returns_remote_data(): void
    {
        config(['services.waha.base_url' => 'http://waha.test']);
        Http::fake([
            'http://waha.test/api/sessions/default' => Http::response(['name' => 'default', 'status' => 'WORKING'], 200),
        ]);

        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/whatsapp/waha/status');

        $response->assertOk()
            ->assertJsonPath('data.status', 'WORKING');
    }
}
