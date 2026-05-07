<?php

namespace App\Jobs\Auth;

use App\Mail\OtpMail;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $email,
        public readonly string $otpCode,
        public readonly string $verificationId,
        public readonly string $userName = 'User',
    ) {}

    public function handle(WhatsappSenderService $whatsappSenderService): void
    {
        $message = "Kode OTP Zaid kamu: {$this->otpCode}. Berlaku 5 menit. Jangan bagikan kode ini ke siapa pun.";

        Log::info('Attempting OTP delivery via WhatsApp.', [
            'phone_number' => $this->phoneNumber,
            'verification_id' => $this->verificationId,
        ]);

        $sentViaWhatsapp = $whatsappSenderService->send($this->phoneNumber, $message);

        if ($sentViaWhatsapp) {
            Log::info('OTP delivered via WhatsApp.', [
                'phone_number' => $this->phoneNumber,
                'verification_id' => $this->verificationId,
            ]);

            return;
        }

        Log::warning('WhatsApp OTP delivery failed. Falling back to email.', [
            'phone_number' => $this->phoneNumber,
            'email' => $this->email,
            'verification_id' => $this->verificationId,
        ]);

        Mail::to($this->email)->send(new OtpMail(
            otpCode: $this->otpCode,
            userName: $this->userName,
        ));
    }
}
