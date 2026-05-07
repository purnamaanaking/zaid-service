<?php

namespace Tests\Feature\Whatsapp;

use App\Models\User;
use App\Models\UserPhone;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
{
    public function test_webhook_verify_returns_challenge(): void
    {
        config(['services.whatsapp.verify_token' => 'test-verify-token']);

        $response = $this->get('/api/v1/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=test-verify-token&hub_challenge=challenge123');

        $response->assertOk();
        $this->assertSame('challenge123', $response->getContent());
    }

    public function test_webhook_verify_rejects_bad_token(): void
    {
        config(['services.whatsapp.verify_token' => 'test-verify-token']);

        $response = $this->get('/api/v1/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong-token&hub_challenge=challenge123');

        $response->assertForbidden();
    }

    public function test_inbound_from_unknown_sender_returns_202(): void
    {
        $response = $this->postJson('/api/v1/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid-unknown-1',
                                        'from' => '628999999999',
                                        'timestamp' => '1710000000',
                                        'text' => ['body' => 'cek agenda'],
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(202);
    }

    public function test_inbound_from_verified_sender_is_processed(): void
    {
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
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid-verified-1',
                                        'from' => '628123456789',
                                        'timestamp' => '1710000000',
                                        'text' => ['body' => 'cek agenda hari ini'],
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(202);
    }
}
