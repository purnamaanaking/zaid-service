<?php

namespace Tests\Feature\Whatsapp;

use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WahaWebhookTest extends TestCase
{
    private function fakeOpenAiAgentResponse(string $reply, ?array $action = null): void
    {
        $json = json_encode(['reply' => $reply, 'action' => $action]);

        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $json]]],
            ]),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_waha_inbound_message_is_processed(): void
    {
        config(['services.whatsapp.driver' => 'waha']);
        $this->fakeOpenAiAgentResponse('Jadwal hari ini kosong.');

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

    public function test_waha_unknown_sender_receives_web_registration_reply(): void
    {
        config([
            'services.whatsapp.driver' => 'waha',
            'services.waha.base_url' => 'http://waha.test',
            'services.waha.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://waha.test/api/sendText' => Http::response(['success' => true], 201),
        ]);

        $response = $this->postJson('/api/v1/webhooks/whatsapp', [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'false_628999999999@c.us_UNKNOWN_1',
                'from' => '628999999999@c.us',
                'fromMe' => false,
                'to' => '628111111111@c.us',
                'body' => 'halo',
                'hasMedia' => false,
                'source' => 'app',
            ],
        ]);

        $response->assertStatus(202);

        Http::assertSent(fn ($request) =>
            $request->url() === 'http://waha.test/api/sendText'
            && $request['chatId'] === '628999999999@c.us'
            && str_contains($request['text'], 'https://zaid-assist.my.id/')
        );
    }

    public function test_waha_image_message_is_processed_using_downloaded_media(): void
    {
        config([
            'services.whatsapp.driver' => 'waha',
            'services.waha.base_url' => 'http://waha.test',
        ]);

        Http::fake([
            'http://file.waha.test/image.jpg' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['reply' => 'Aku lihat gambarnya.', 'action' => null])]]],
            ]),
            '*' => Http::response([], 200),
        ]);

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
                'id' => 'false_628123456789@c.us_IMAGE_1',
                'from' => '628123456789@c.us',
                'fromMe' => false,
                'to' => '628111111111@c.us',
                'body' => 'Ini gambar apa',
                'hasMedia' => true,
                'source' => 'app',
                'media' => [
                    'url' => 'http://file.waha.test/image.jpg',
                    'mimetype' => 'image/jpeg',
                ],
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
