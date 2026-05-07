<?php

namespace Tests\Unit\Integrations;

use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Services\Integrations\GoogleCalendarEventTransformer;
use Carbon\Carbon;
use Tests\TestCase;

class GoogleCalendarEventTransformerTest extends TestCase
{
    public function test_it_transforms_scheduled_task_into_google_event_payload(): void
    {
        $task = new Task([
            'title' => 'Meeting Client',
            'description' => 'Discuss roadmap',
            'scheduled_date' => Carbon::parse('2026-05-23'),
            'scheduled_time' => '10:00:00',
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
        ]);

        $payload = app(GoogleCalendarEventTransformer::class)->taskToGoogleEvent($task);

        $this->assertSame('Meeting Client', $payload['summary']);
        $this->assertSame('Discuss roadmap', $payload['description']);
        $this->assertSame('Asia/Jakarta', $payload['start']['timeZone']);
        $this->assertSame('2026-05-23T10:00:00+07:00', $payload['start']['dateTime']);
        $this->assertSame('2026-05-23T11:00:00+07:00', $payload['end']['dateTime']);
    }

    public function test_it_transforms_all_day_task_into_google_event_payload(): void
    {
        $task = new Task([
            'title' => 'Hari Libur',
            'scheduled_date' => Carbon::parse('2026-05-23'),
            'timezone' => 'Asia/Jakarta',
            'all_day' => true,
        ]);

        $payload = app(GoogleCalendarEventTransformer::class)->taskToGoogleEvent($task);

        $this->assertSame('2026-05-23', $payload['start']['date']);
        $this->assertSame('2026-05-24', $payload['end']['date']);
        $this->assertArrayNotHasKey('dateTime', $payload['start']);
    }

    public function test_it_adds_simple_weekly_rrule_when_task_is_recurring(): void
    {
        $task = new Task([
            'title' => 'Weekly Sync',
            'scheduled_date' => Carbon::parse('2026-05-23'),
            'scheduled_time' => '09:00:00',
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
            'is_recurring' => true,
        ]);
        $task->setRelation('recurrence', new TaskRecurrence([
            'recurrence_type' => 'weekly',
            'interval_value' => 1,
            'day_of_week' => 'friday',
        ]));

        $payload = app(GoogleCalendarEventTransformer::class)->taskToGoogleEvent($task);

        $this->assertSame(['RRULE:FREQ=WEEKLY;INTERVAL=1;BYDAY=FR'], $payload['recurrence']);
    }

    public function test_it_transforms_google_event_into_local_task_payload(): void
    {
        $event = [
            'summary' => 'Google Event',
            'description' => 'Imported from Google',
            'status' => 'confirmed',
            'start' => [
                'dateTime' => '2026-05-23T10:00:00+07:00',
                'timeZone' => 'Asia/Jakarta',
            ],
            'end' => [
                'dateTime' => '2026-05-23T11:00:00+07:00',
                'timeZone' => 'Asia/Jakarta',
            ],
        ];

        $payload = app(GoogleCalendarEventTransformer::class)->googleEventToTaskData($event);

        $this->assertSame('Google Event', $payload['title']);
        $this->assertSame('Imported from Google', $payload['description']);
        $this->assertSame('2026-05-23', $payload['scheduled_date']);
        $this->assertSame('10:00:00', $payload['scheduled_time']);
        $this->assertSame('Asia/Jakarta', $payload['timezone']);
        $this->assertFalse($payload['all_day']);
        $this->assertSame('pending', $payload['status']);
    }
}
