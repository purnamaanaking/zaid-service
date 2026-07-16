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
            ->assertSee('Why Google Sign-In is required')
            ->assertSee('Google Sign-In only to securely authenticate users')
            ->assertSee('Does Zaid access my Google Calendar or Tasks?')
            ->assertDontSee('Google Calendar Sync')
            ->assertDontSee('Google Tasks Sync')
            ->assertSee('OAuth 2.0 authentication')
            ->assertSee('How it works')
            ->assertSee('zaidassistant@gmail.com')
            ->assertSee('https://zaidassistant.id');
    }

    public function test_homepage_exposes_required_public_links(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('href="/app"', false)
            ->assertSee('href="/privacy"', false)
            ->assertSee('href="/terms"', false)
            ->assertSee('href="mailto:zaidassistant@gmail.com"', false);
    }

    public function test_privacy_policy_explains_google_sign_in_only(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Google Sign-In')
            ->assertDontSee('Google Calendar data')
            ->assertDontSee('Google Tasks data')
            ->assertSee('request deletion')
            ->assertSee('zaidassistant@gmail.com');
    }

    public function test_terms_explain_google_sign_in_only(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('Google Sign-In')
            ->assertDontSee('Google Calendar and Google Tasks')
            ->assertSee('zaidassistant@gmail.com');
    }

    public function test_app_flow_is_public_and_explains_each_onboarding_stage(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee('Verify your phone')
            ->assertSee("window.location.assign('/dashboard')", false)
            ->assertSee('OTP session expired. Send a new code.')
            ->assertDontSee('Connect Calendar and Tasks')
            ->assertDontSee('/integrations/google-calendar/connect')
            ->assertSee('Plan clearly.')
            ->assertSee('Set up your workspace')
            ->assertSee('Onboarding progress')
            ->assertSee('Waiting for your first step.')
            ->assertSee('Privacy Policy')
            ->assertSee('Terms of Service');
    }

    public function test_dashboard_renders_workspace_shell(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Zaid Workspace')
            ->assertSee('Calendar')
            ->assertSee('Ask Zaid')
            ->assertSee('/calendar/month')
            ->assertSee('/agenda/day');
    }
}
