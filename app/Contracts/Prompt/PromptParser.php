<?php

namespace App\Contracts\Prompt;

interface PromptParser
{
    /**
     * @param  array<int, array{type: string, url?: string, mime_type?: string}>|null  $attachments
     * @return array{
     *   intent: string,
     *   confidence_score: float,
     *   entities: array<string, mixed>,
     *   requires_confirmation: bool,
     *   parse_status: string,
     * }
     */
    public function parse(string $text, string $userId, ?array $attachments = null): array;
}
