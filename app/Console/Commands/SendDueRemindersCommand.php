<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Console\Command;

class SendDueRemindersCommand extends Command
{
    protected $signature = 'reminders:send-due';
    protected $description = 'Send due event reminders';

    public function handle(WhatsappSenderService $sender): int
    {
        Reminder::query()->where('status', 'pending')->where('remind_at', '<=', now())->whereHas('calendarEvent')->with(['calendarEvent', 'user.phones'])->orderBy('remind_at')->limit(100)->get()
            ->each(function (Reminder $reminder) use ($sender): void {
                $event = $reminder->calendarEvent;
                $text = "Reminder: {$event->title} pada ".$event->starts_at->format('Y-m-d H:i').' sebentar lagi.';
                $phone = $reminder->user->phones->first(fn ($phone) => $phone->is_verified && $phone->linked_for_whatsapp_at !== null);
                if (in_array($reminder->channel, ['whatsapp', 'both'], true) && $phone === null) {
                    $reminder->update(['status' => 'failed', 'error_message' => 'No verified WhatsApp phone.']);
                    return;
                }
                $sent = $reminder->channel === 'app' || ($phone !== null && $sender->send($phone->phone_e164, $text));
                $reminder->update(['status' => $sent ? 'sent' : 'failed', 'sent_at' => $sent ? now() : null, 'error_message' => $sent ? null : 'WhatsApp delivery failed.']);
            });
        return self::SUCCESS;
    }
}
