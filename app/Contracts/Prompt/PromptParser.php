<?php

namespace App\Contracts\Prompt;

interface PromptParser
{
    /**
     * @return array{
     *   intent: string,
     *   confidence_score: float,
     *   entities: array<string, mixed>,
     *   requires_confirmation: bool,
     *   parse_status: string,
     * }
     */
    public function parse(string $text, string $userId): array;
}
