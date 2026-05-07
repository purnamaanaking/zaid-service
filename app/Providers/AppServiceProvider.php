<?php

namespace App\Providers;

use App\Contracts\Auth\GoogleTokenVerifier;
use App\Contracts\Prompt\PromptParser;
use App\Services\Auth\SocialiteGoogleTokenVerifier;
use App\Services\Prompt\OpenAiPromptParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GoogleTokenVerifier::class, SocialiteGoogleTokenVerifier::class);

        $this->app->bind(PromptParser::class, function (): OpenAiPromptParser {
            return new OpenAiPromptParser(
                modelText: config('services.openai.model_text', 'MiniMax-M2.7-highspeed'),
                modelMultimodal: config('services.openai.model_multimodal', 'gemini/gemini-2.0-flash'),
                apiKey: config('services.openai.api_key', ''),
                apiBase: config('services.openai.api_base', 'https://api.openai.com/v1'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
