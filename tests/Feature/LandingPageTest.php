<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_homepage_is_public_and_contains_oauth_verification_content(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Zaid Assistant')
            ->assertSee('Manage Google Calendar events')
            ->assertSee('Manage Google Tasks')
            ->assertSee('Why Google Sign-In is required')
            ->assertSee('Requested permissions')
            ->assertSee('Google Calendar')
            ->assertSee('Google Tasks')
            ->assertSee('User Profile')
            ->assertSee('Email Address')
            ->assertSee('We never access, modify, or share user data without user interaction or permission.')
            ->assertSee('OAuth 2.0 authentication')
            ->assertSee('How it works')
            ->assertSee('zaidassistant@gmail.com')
            ->assertSee('https://zaidassistant.id')
            ->assertSee('Google API Services User Data Policy');
    }

    public function test_homepage_exposes_required_public_links(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('href="/app"', false)
            ->assertSee('href="/privacy"', false)
            ->assertSee('href="/terms"', false)
            ->assertSee('href="mailto:zaidassistant@gmail.com"', false)
            ->assertSee('href="https://developers.google.com/terms/api-services-user-data-policy"', false);
    }

    public function test_privacy_policy_explains_google_data_use_and_user_controls(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Google Calendar data')
            ->assertSee('Google Tasks data')
            ->assertSee('Google API Services User Data Policy')
            ->assertSee('revoke access')
            ->assertSee('request deletion')
            ->assertSee('zaidassistant@gmail.com');
    }

    public function test_terms_explain_google_integrations_and_user_responsibility(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('Google Calendar and Google Tasks')
            ->assertSee('directly request')
            ->assertSee('revoke access')
            ->assertSee('zaidassistant@gmail.com');
    }

    public function test_app_flow_is_public_and_explains_each_onboarding_stage(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee('Verify your phone')
            ->assertSee('Connect Calendar and Tasks')
            ->assertSee('Privacy Policy')
            ->assertSee('Terms of Service');
    }
}
