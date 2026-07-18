<?php

namespace Tests\Unit\Prompt;

use App\Services\Prompt\OpenAiPromptParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiPromptParserTest extends TestCase
{
    public function test_it_retries_invalid_json_without_requiring_user_to_send_again(): void
    {
        Http::fake([
            'https://example.test/chat/completions' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => '']]]], 200)
                ->push(['choices' => [['message' => ['content' => json_encode([
                    'intent' => 'READ',
                    'confidence_score' => 0.95,
                    'parse_status' => 'parsed',
                    'requires_confirmation' => false,
                    'entities' => [
                        'entity_type' => 'event',
                        'scheduled_date' => now()->format('Y-m-d'),
                        'search_query' => 'cek jadwal hari ini',
                    ],
                ])]]]], 200),
        ]);
        $parser = new OpenAiPromptParser('model', 'model', 'key', 'https://example.test');

        $result = $parser->parse('cek jadwal hari ini', 'user-123');

        $this->assertSame('parsed', $result['parse_status']);
        Http::assertSentCount(2);
    }

    public function test_it_retries_failed_calendar_parse_even_when_text_contains_conversation_context(): void
    {
        Http::fake([
            'https://example.test/chat/completions' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => json_encode([
                    'intent' => 'READ',
                    'confidence_score' => 0,
                    'parse_status' => 'failed',
                    'requires_confirmation' => false,
                    'entities' => [],
                ])]]]], 200)
                ->push(['choices' => [['message' => ['content' => json_encode([
                    'intent' => 'READ',
                    'confidence_score' => 0.95,
                    'parse_status' => 'parsed',
                    'requires_confirmation' => false,
                    'entities' => [
                        'entity_type' => 'event',
                        'scheduled_date' => now()->subDay()->format('Y-m-d'),
                        'search_query' => 'jadwal kemarin',
                    ],
                ])]]]], 200),
        ]);
        $parser = new OpenAiPromptParser('model', 'model', 'key', 'https://example.test');

        $result = $parser->parse("Conversation context:\nUser: cek jadwal hari ini\n\nCurrent user message: jadwal kemarin apa?", 'user-123');

        $this->assertSame('parsed', $result['parse_status']);
        Http::assertSentCount(2);
    }

    public function test_it_retries_with_more_interpretive_prompt_when_first_parse_returns_unsupported_for_task_related_text(): void
    {
        Http::fake([
            'https://example.test/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([
                                'intent' => 'READ',
                                'confidence_score' => 0.22,
                                'parse_status' => 'unsupported',
                                'requires_confirmation' => false,
                                'entities' => [
                                    'entity_type' => 'task',
                                    'title' => null,
                                    'scheduled_date' => null,
                                    'scheduled_time' => null,
                                    'all_day' => false,
                                    'recurrence' => null,
                                    'description' => null,
                                    'search_query' => null,
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ]],
                ], 200)
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([
                                'intent' => 'READ',
                                'confidence_score' => 0.91,
                                'parse_status' => 'parsed',
                                'requires_confirmation' => false,
                                'entities' => [
                                    'entity_type' => 'task',
                                    'title' => null,
                                    'scheduled_date' => '2026-05-22',
                                    'scheduled_time' => null,
                                    'all_day' => false,
                                    'recurrence' => null,
                                    'description' => null,
                                    'search_query' => 'cek tanggal 22 mei jadwal apa',
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ]],
                ], 200),
        ]);

        $parser = new OpenAiPromptParser(
            modelText: 'gpt-5.4',
            modelMultimodal: 'gpt-5.4',
            apiKey: 'test-key',
            apiBase: 'https://example.test',
        );

        $result = $parser->parse('Cek tanggal 22 mei jadwal apa ya, gue ada apa aja?', 'user-123');

        $this->assertSame('parsed', $result['parse_status']);
        $this->assertSame('READ', $result['intent']);
        $this->assertSame('2026-05-22', $result['entities']['scheduled_date']);

        Http::assertSentCount(2);
    }
}
