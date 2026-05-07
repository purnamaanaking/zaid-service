<?php

namespace App\Jobs\Auth;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $otpCode,
        public readonly string $verificationId,
    ) {}

    public function handle(): void
    {
        Log::info('Dispatching OTP code.', [
            'phone_number' => $this->phoneNumber,
            'verification_id' => $this->verificationId,
        ]);
    }
}
