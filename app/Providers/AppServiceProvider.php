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
                model: config('services.openai.model', 'gpt-4o-mini'),
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
