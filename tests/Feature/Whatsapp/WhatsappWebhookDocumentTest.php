<?php

namespace Tests\Feature\Whatsapp;

use App\Contracts\Prompt\PromptParser;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use App\Services\Documents\DocumentTextExtractor;
use App\Services\Whatsapp\WahaApiService;
use App\Services\Whatsapp\WhatsappSenderService;
use Mockery;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class WhatsappWebhookDocumentTest extends TestCase
{
    public function test_waha_inbound_pdf_document_extracts_text_and_lists_schedule(): void
    {
        config(['services.whatsapp.driver' => 'waha']);

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281234567890',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $mockWahaApi = Mockery::mock(WahaApiService::class);
        $mockWahaApi->shouldReceive('downloadMediaContent')
            ->once()
            ->with('https://waha.zaidassistant.id/api/files/session_1/test.pdf')
            ->andReturn('%PDF-1.4 mock content');
        $this->app->instance(WahaApiService::class, $mockWahaApi);

        $mockExtractor = Mockery::mock(DocumentTextExtractor::class);
        $mockExtractor->shouldReceive('extract')
            ->once()
            ->andReturn("NO | TANGGAL | KATEGORI | RENCANA AKTIVITAS | WAKTU\n8 | 18/10/2026 (Minggu) | Hobi | Rakit modul sensor elektronika | 13:00 - 16:30\n9 | 19/10/2026 (Senin) | Kerja | Standup meeting | 09:00 - 10:00");
        $this->app->instance(DocumentTextExtractor::class, $mockExtractor);

        $mockSender = Mockery::mock(WhatsappSenderService::class);
        $mockSender->shouldReceive('send')
            ->once()
            ->with('+6281234567890', Mockery::on(function (string $reply) {
                return str_contains($reply, 'Rakit modul sensor elektronika')
                    && str_contains($reply, '2026-10-18')
                    && ! str_contains($reply, 'Standup meeting');
            }))
            ->andReturn(true);
        $this->app->instance(WhatsappSenderService::class, $mockSender);

        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 1.0,
            'parse_status' => 'parsed',
            'entities' => [],
        ]));

        $payload = [
            'event' => 'message',
            'session' => 'session_1',
            'payload' => [
                'id' => 'wa_msg_pdf_test_1',
                'from' => '6281234567890@c.us',
                'to' => 'bot@c.us',
                'body' => 'list jadwalnya coba terkait hobi',
                'fromMe' => false,
                'hasMedia' => true,
                'media' => [
                    'url' => 'https://waha.zaidassistant.id/api/files/session_1/test.pdf',
                    'mimetype' => 'application/pdf',
                    'filename' => 'Random_Kegiatan_Oktober_2026.pdf',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);

        $response->assertStatus(202);

        $this->assertDatabaseHas('whatsapp_messages', [
            'wa_message_id' => 'wa_msg_pdf_test_1',
            'direction' => 'inbound',
            'processing_status' => 'executed',
        ]);
    }

    public function test_followup_prompt_can_select_candidate_by_number(): void
    {
        config(['services.whatsapp.driver' => 'waha']);

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281234567891',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $mockSender = Mockery::mock(WhatsappSenderService::class);
        $mockSender->shouldReceive('send')
            ->once()
            ->with('+6281234567891', Mockery::on(function (string $reply) {
                return str_contains($reply, 'Rakit modul sensor elektronika')
                    && str_contains($reply, 'sudah masuk agenda');
            }))
            ->andReturn(true);
        $this->app->instance(WhatsappSenderService::class, $mockSender);

        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'entities' => [
                'action' => 'CREATE',
            ],
        ]));

        \App\Models\PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'raw_text' => 'list jadwal di pdf',
            'normalized_text' => 'list jadwal di pdf',
            'intent' => 'READ',
            'confidence_score' => 1.0,
            'parse_status' => 'ambiguous',
            'execution_status' => 'awaiting_confirmation',
            'extracted_entities' => [
                'document_text' => 'some text',
                'document_candidates' => [
                    [
                        'title' => 'Rakit modul sensor elektronika',
                        'scheduled_date' => '2026-10-18',
                        'scheduled_time' => '13:00:00',
                        'scheduled_end_time' => '16:30:00',
                        'category' => 'Hobi',
                        'location' => null,
                        'searchable' => 'Rakit modul sensor elektronika Hobi',
                    ],
                    [
                        'title' => 'Standup meeting',
                        'scheduled_date' => '2026-10-19',
                        'scheduled_time' => '09:00:00',
                        'scheduled_end_time' => '10:00:00',
                        'category' => 'Kerja',
                        'location' => null,
                        'searchable' => 'Standup meeting Kerja',
                    ],
                ],
            ],
        ]);

        $payload = [
            'event' => 'message',
            'session' => 'session_1',
            'payload' => [
                'id' => 'wa_msg_number_select_1',
                'from' => '6281234567891@c.us',
                'to' => 'bot@c.us',
                'body' => 'catat yang nomor 1',
                'fromMe' => false,
                'hasMedia' => false,
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);

        $response->assertStatus(202);

        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $user->id,
            'title' => 'Rakit modul sensor elektronika',
        ]);
    }

    public function test_followup_prompt_can_create_all_candidates_with_catat_semua(): void
    {
        config(['services.whatsapp.driver' => 'waha']);

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281234567892',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $mockSender = Mockery::mock(WhatsappSenderService::class);
        $mockSender->shouldReceive('send')
            ->once()
            ->with('+6281234567892', Mockery::on(function (string $reply) {
                return str_contains($reply, '2 jadwal')
                    && str_contains($reply, 'sudah masuk agenda');
            }))
            ->andReturn(true);
        $this->app->instance(WhatsappSenderService::class, $mockSender);

        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'entities' => [
                'action' => 'CREATE',
            ],
        ]));

        \App\Models\PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'raw_text' => 'list jadwal di pdf',
            'normalized_text' => 'list jadwal di pdf',
            'intent' => 'READ',
            'confidence_score' => 1.0,
            'parse_status' => 'ambiguous',
            'execution_status' => 'awaiting_confirmation',
            'extracted_entities' => [
                'document_text' => 'some text',
                'document_candidates' => [
                    [
                        'title' => 'Rakit modul sensor elektronika',
                        'scheduled_date' => '2026-10-18',
                        'scheduled_time' => '13:00:00',
                        'scheduled_end_time' => '16:30:00',
                        'category' => 'Hobi',
                        'location' => null,
                        'searchable' => 'Rakit modul sensor elektronika Hobi',
                    ],
                    [
                        'title' => 'Standup meeting',
                        'scheduled_date' => '2026-10-19',
                        'scheduled_time' => '09:00:00',
                        'scheduled_end_time' => '10:00:00',
                        'category' => 'Kerja',
                        'location' => null,
                        'searchable' => 'Standup meeting Kerja',
                    ],
                ],
            ],
        ]);

        $payload = [
            'event' => 'message',
            'session' => 'session_1',
            'payload' => [
                'id' => 'wa_msg_catat_semua_1',
                'from' => '6281234567892@c.us',
                'to' => 'bot@c.us',
                'body' => 'catat semua',
                'fromMe' => false,
                'hasMedia' => false,
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);

        $response->assertStatus(202);

        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $user->id,
            'title' => 'Rakit modul sensor elektronika',
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $user->id,
            'title' => 'Standup meeting',
        ]);
    }

    public function test_space_delimited_pdf_with_cek_jadwal_hobi_never_creates_and_only_lists_hobby(): void
    {
        config(['services.whatsapp.driver' => 'waha']);

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281234567893',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $mockWahaApi = Mockery::mock(WahaApiService::class);
        $mockWahaApi->shouldReceive('downloadMediaContent')
            ->once()
            ->andReturn('%PDF-1.7 mock content');
        $this->app->instance(WahaApiService::class, $mockWahaApi);

        $spaceTableText = "NO     TANGGAL           KATEGORI         RENCANA AKTIVITAS                                           WAKTU                    VIBE\n".
            "1      03/10/2026        Social / Fun     Nongkrong di coffee shop & mabar game santai                19:00 - 22:30             Relax\n".
            "8      18/10/2026        Hobi             Rakit modul sensor elektronika & eksperimen 3D              13:00 - 16:30            Creative\n".
            "       (Minggu)                           modeling";

        $mockExtractor = Mockery::mock(DocumentTextExtractor::class);
        $mockExtractor->shouldReceive('extract')
            ->once()
            ->andReturn($spaceTableText);
        $this->app->instance(DocumentTextExtractor::class, $mockExtractor);

        $mockSender = Mockery::mock(WhatsappSenderService::class);
        $mockSender->shouldReceive('send')
            ->once()
            ->with('+6281234567893', Mockery::on(function (string $reply) {
                return str_contains($reply, 'Rakit modul sensor elektronika & eksperimen 3D modeling')
                    && str_contains($reply, '2026-10-18')
                    && ! str_contains($reply, 'Nongkrong di coffee shop')
                    && ! str_contains($reply, 'sudah saya tambahkan');
            }))
            ->andReturn(true);
        $this->app->instance(WhatsappSenderService::class, $mockSender);

        // Even if AI parser mistakenly returned CREATE_EVENTS, safety guard should intercept
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 1.0,
            'parse_status' => 'parsed',
            'entities' => [
                'action' => 'CREATE_EVENTS',
                'human_response' => 'Jadwal hobi sudah saya tambahkan',
            ],
        ]));

        $payload = [
            'event' => 'message',
            'session' => 'session_1',
            'payload' => [
                'id' => 'wa_msg_cek_hobi_disni',
                'from' => '6281234567893@c.us',
                'to' => 'bot@c.us',
                'body' => 'cek jadwal hobi bro disni',
                'fromMe' => false,
                'hasMedia' => true,
                'media' => [
                    'url' => 'https://waha.zaidassistant.id/api/files/session_1/Random_Kegiatan_Oktober_2026.pdf',
                    'mimetype' => 'application/pdf',
                    'filename' => 'Random_Kegiatan_Oktober_2026.pdf',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);

        $response->assertStatus(202);

        // Verify NO calendar event was created
        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_typo_caption_list_semua_ajdwal_nya_yang_hobi_aja_filters_only_hobby(): void
    {
        config(['services.whatsapp.driver' => 'waha']);

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281234567894',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $mockWahaApi = Mockery::mock(WahaApiService::class);
        $mockWahaApi->shouldReceive('downloadMediaContent')->once()->andReturn('%PDF content');
        $this->app->instance(WahaApiService::class, $mockWahaApi);

        $spaceTableText = "NO     TANGGAL           KATEGORI         RENCANA AKTIVITAS                                           WAKTU                    VIBE\n".
            "1      03/10/2026        Social / Fun     Nongkrong di coffee shop & mabar game santai                19:00 - 22:30             Relax\n".
            "8      18/10/2026        Hobi             Rakit modul sensor elektronika & eksperimen 3D              13:00 - 16:30            Creative\n".
            "       (Minggu)                           modeling";

        $mockExtractor = Mockery::mock(DocumentTextExtractor::class);
        $mockExtractor->shouldReceive('extract')->once()->andReturn($spaceTableText);
        $this->app->instance(DocumentTextExtractor::class, $mockExtractor);

        $mockSender = Mockery::mock(WhatsappSenderService::class);
        $mockSender->shouldReceive('send')
            ->once()
            ->with('+6281234567894', Mockery::on(function (string $reply) {
                return str_contains($reply, 'Daftar jadwal di dokumen (1 kegiatan):')
                    && str_contains($reply, 'Rakit modul sensor elektronika & eksperimen 3D modeling')
                    && ! str_contains($reply, 'Nongkrong di coffee shop');
            }))
            ->andReturn(true);
        $this->app->instance(WhatsappSenderService::class, $mockSender);

        $payload = [
            'event' => 'message',
            'session' => 'session_1',
            'payload' => [
                'id' => 'wa_msg_typo_hobi',
                'from' => '6281234567894@c.us',
                'to' => 'bot@c.us',
                'body' => 'list semua ajdwal nya bro yang hobi aja',
                'fromMe' => false,
                'hasMedia' => true,
                'media' => [
                    'url' => 'https://waha.zaidassistant.id/api/files/session_1/test.pdf',
                    'mimetype' => 'application/pdf',
                    'filename' => 'Random_Kegiatan_Oktober_2026.pdf',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);
        $response->assertStatus(202);
    }

    public function test_capability_question_lu_bisa_baca_pdf_answers_helpfully_without_searching_document(): void
    {
        config(['services.whatsapp.driver' => 'waha']);

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281234567895',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $mockSender = Mockery::mock(WhatsappSenderService::class);
        $mockSender->shouldReceive('send')
            ->once()
            ->with('+6281234567895', Mockery::on(function (string $reply) {
                return str_contains($reply, 'Bisa banget!')
                    && ! str_contains($reply, 'Tidak ditemukan jadwal');
            }))
            ->andReturn(true);
        $this->app->instance(WhatsappSenderService::class, $mockSender);

        $payload = [
            'event' => 'message',
            'session' => 'session_1',
            'payload' => [
                'id' => 'wa_msg_can_read_pdf',
                'from' => '6281234567895@c.us',
                'to' => 'bot@c.us',
                'body' => 'lu bisa baca pdf ?',
                'fromMe' => false,
                'hasMedia' => false,
            ],
        ];

        $response = $this->postJson('/api/v1/webhooks/whatsapp', $payload);
        $response->assertStatus(202);
    }
}
