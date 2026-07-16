<?php

namespace Tests\Feature\Whatsapp;

use App\Models\User;
use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WahaWebhookDedupAndLidTest extends TestCase
{
    private function fakeAgentAndWaha(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['reply' => 'Yo!', 'action' => null])]]],
            ]),
            'http://waha.test/api/default/lids/*' => Http::response([
                'lid' => '192002369028278@lid',
                'pn' => '6281556796240@c.us',
            ], 200),
            'http://waha.test/api/sendText' => Http::response(['success' => true], 201),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_waha_lid_message_is_matched_to_verified_user_via_lid_lookup(): void
    {
        config([
            'services.whatsapp.driver' => 'waha',
            'services.waha.base_url' => 'http://waha.test',
            'services.waha.api_key' => 'test-key',
        ]);

        $this->fakeAgentAndWaha();

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796240',
            'phone_local' => '081556796240',
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
                'id' => 'false_192002369028278@lid_ABC123',
                'from' => '192002369028278@lid',
                'fromMe' => false,
                'to' => '6285182302209@c.us',
                'body' => 'halo dari lid',
                'hasMedia' => false,
                'source' => 'app',
            ],
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('whatsapp_messages', [
            'wa_message_id' => 'false_192002369028278@lid_ABC123',
            'user_id' => $user->id,
            'sender_phone_e164' => '+6281556796240',
            'processing_status' => 'executed',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://waha.test/api/sendText'
                && $request['chatId'] === '6281556796240@c.us';
        });
    }

    public function test_waha_lid_message_prefers_embedded_phone_number(): void
    {
        config(['services.whatsapp.driver' => 'waha']);
        $this->fakeAgentAndWaha();

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796240',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        app(\App\Services\Whatsapp\WhatsappWebhookService::class)->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'lid-with-alt-phone',
                'from' => '192002369028278@lid',
                'fromMe' => false,
                'to' => '6285182302209@c.us',
                'body' => 'halo',
                '_data' => ['key' => ['remoteJidAlt' => '6281556796240@s.whatsapp.net']],
            ],
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'wa_message_id' => 'lid-with-alt-phone',
            'user_id' => $user->id,
            'sender_phone_e164' => '+6281556796240',
            'processing_status' => 'executed',
        ]);
    }

    public function test_duplicate_waha_events_with_same_message_id_are_ignored_without_failing(): void
    {
        config([
            'services.whatsapp.driver' => 'waha',
            'services.waha.base_url' => 'http://waha.test',
            'services.waha.api_key' => 'test-key',
        ]);

        $this->fakeAgentAndWaha();

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

        $payload = [
            'event' => 'message',
            'session' => 'default',
            'payload' => [
                'id' => 'false_628123456789@c.us_DUPLICATE1',
                'from' => '628123456789@c.us',
                'fromMe' => false,
                'to' => '6285182302209@c.us',
                'body' => 'pesan dobel',
                'hasMedia' => false,
                'source' => 'app',
            ],
        ];

        $first = $this->postJson('/api/v1/webhooks/whatsapp', $payload);
        $second = $this->postJson('/api/v1/webhooks/whatsapp', $payload);

        $first->assertStatus(202);
        $second->assertStatus(202);

        $this->assertSame(1, WhatsappMessage::query()->where('wa_message_id', 'false_628123456789@c.us_DUPLICATE1')->count());
    }
}
