<?php

namespace Tests\Unit\Auth;

use App\Jobs\Auth\SendOtpJob;
use App\Mail\OtpMail;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SendOtpJobTest extends TestCase
{
    public function test_otp_prefers_whatsapp_delivery(): void
    {
        Mail::fake();

        $sender = Mockery::mock(WhatsappSenderService::class);
        $sender->shouldReceive('send')
            ->once()
            ->with('+628123456789', Mockery::type('string'))
            ->andReturn(true);

        $job = new SendOtpJob('+628123456789', 'user@example.com', '123456', 'verification-id', 'User');
        $job->handle($sender);

        Mail::assertNothingSent();
    }

    public function test_otp_falls_back_to_email_when_whatsapp_fails(): void
    {
        Mail::fake();

        $sender = Mockery::mock(WhatsappSenderService::class);
        $sender->shouldReceive('send')
            ->once()
            ->andReturn(false);

        $job = new SendOtpJob('+628123456789', 'user@example.com', '123456', 'verification-id', 'User');
        $job->handle($sender);

        Mail::assertSent(OtpMail::class, 1);
    }
}
