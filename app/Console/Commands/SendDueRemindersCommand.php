<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Console\Command;

class SendDueRemindersCommand extends Command
{
    protected $signature = 'reminders:send-due';

    protected $description = 'Send due task and event reminders';

    public function handle(WhatsappSenderService $sender): int
    {
        Reminder::query()
            ->where('status', 'pending')
            ->where('remind_at', '<=', now())
            ->where(function ($query): void {
                $query->whereHas('task', fn ($task) => $task->where('status', 'pending'))
                    ->orWhereHas('calendarEvent');
            })
            ->with(['task', 'calendarEvent', 'user.phones'])
            ->orderBy('remind_at')
            ->limit(100)
            ->get()
            ->each(function (Reminder $reminder) use ($sender): void {
                $item = $reminder->task ?: $reminder->calendarEvent;
                if ($item === null) {
                    $reminder->update(['status' => 'failed', 'error_message' => 'Reminder source no longer exists.']);
                    return;
                }
                $start = $reminder->task
                    ? ($item->scheduled_date?->format('Y-m-d').' '.substr((string) $item->scheduled_time, 0, 5))
                    : $item->starts_at?->format('Y-m-d H:i');
                $text = "Reminder: {$item->title}".($start ? " pada {$start}" : '').' sebentar lagi.';
                $phone = $reminder->user->phones
                    ->first(fn ($phone) => $phone->is_verified && $phone->linked_for_whatsapp_at !== null);

                if (in_array($reminder->channel, ['whatsapp', 'both'], true) && $phone === null) {
                    $reminder->update(['status' => 'failed', 'error_message' => 'No verified WhatsApp phone.']);
                    return;
                }

                $sent = $reminder->channel === 'app'
                    || ($phone !== null && $sender->send($phone->phone_e164, $text));
                $reminder->update([
                    'status' => $sent ? 'sent' : 'failed',
                    'sent_at' => $sent ? now() : null,
                    'error_message' => $sent ? null : 'WhatsApp delivery failed.',
                ]);
            });

        return self::SUCCESS;
    }
}
