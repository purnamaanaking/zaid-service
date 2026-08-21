<?php

namespace Tests\Fakes\Prompt;

use App\Contracts\Prompt\PromptParser;

class FakePromptParser implements PromptParser
{
    public ?string $lastContext = null;

    /**
     * @param  array<string, mixed>|null  $fixedResult
     */
    public function __construct(private readonly ?array $fixedResult = null) {}

    public function parse(string $text, string $userId, ?array $attachments = null): array
    {
        $this->lastContext = $text;

        if ($this->fixedResult !== null) {
            return $this->fixedResult;
        }

        return [
            'intent' => 'READ',
            'confidence_score' => 0.95,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => null,
                'scheduled_date' => now()->format('Y-m-d'),
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'agenda hari ini',
            ],
        ];
    }
}
