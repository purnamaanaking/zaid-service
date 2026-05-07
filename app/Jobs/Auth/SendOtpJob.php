<?php

namespace App\Jobs\Auth;

use App\Mail\OtpMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $email,
        public readonly string $otpCode,
        public readonly string $verificationId,
        public readonly string $userName = 'User',
    ) {}

    public function handle(): void
    {
        Log::info('Sending OTP email.', [
            'email' => $this->email,
            'verification_id' => $this->verificationId,
        ]);

        Mail::to($this->email)->send(new OtpMail(
            otpCode: $this->otpCode,
            userName: $this->userName,
        ));
    }
}
