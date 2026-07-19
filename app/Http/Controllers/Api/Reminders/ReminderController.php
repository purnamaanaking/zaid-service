<?php

namespace App\Http\Controllers\Api\Reminders;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reminders = Reminder::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'sent', 'failed'])
            ->with(['task', 'calendarEvent'])
            ->orderBy('remind_at')
            ->get();

        return response()->json(['success' => true, 'data' => ['items' => $reminders]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        [$source, $sourceKey] = $this->source($request, $data);
        $startsAt = $source instanceof Task
            ? ($source->scheduled_date && $source->scheduled_time ? $source->scheduled_date->format('Y-m-d').' '.$source->scheduled_time : null)
            : $source->starts_at;

        if ($startsAt === null) {
            throw ValidationException::withMessages(['reminder' => 'Item harus punya tanggal dan jam untuk memakai reminder.']);
        }

        $remindAt = now()->parse($startsAt, $source->timezone ?? 'Asia/Jakarta')->subMinutes($data['minutes_before']);
        $reminder = Reminder::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                $sourceKey => $source->id,
                'minutes_before' => $data['minutes_before'],
                'channel' => $data['channel'],
            ],
            ['remind_at' => $remindAt, 'status' => 'pending', 'sent_at' => null, 'error_message' => null],
        );

        return response()->json(['success' => true, 'data' => ['reminder' => $reminder]], 201);
    }

    public function update(Request $request, string $reminderId): JsonResponse
    {
        $reminder = Reminder::query()->where('user_id', $request->user()->id)->findOrFail($reminderId);
        $data = $this->validated($request, true);
        $source = $reminder->task ?: $reminder->calendarEvent;
        $startsAt = $source instanceof Task
            ? ($source->scheduled_date && $source->scheduled_time ? $source->scheduled_date->format('Y-m-d').' '.$source->scheduled_time : null)
            : $source?->starts_at;
        if ($startsAt === null) throw ValidationException::withMessages(['reminder' => 'Item tidak punya waktu mulai.']);
        $reminder->update([
            'minutes_before' => $data['minutes_before'],
            'channel' => $data['channel'],
            'remind_at' => now()->parse($startsAt, $source->timezone ?? 'Asia/Jakarta')->subMinutes($data['minutes_before']),
            'status' => 'pending',
            'sent_at' => null,
            'error_message' => null,
        ]);

        return response()->json(['success' => true, 'data' => ['reminder' => $reminder->fresh()]]);
    }

    public function destroy(Request $request, string $reminderId): JsonResponse
    {
        Reminder::query()->where('user_id', $request->user()->id)->findOrFail($reminderId)->delete();

        return response()->json(['success' => true]);
    }

    private function validated(Request $request, bool $update = false): array
    {
        $rules = [
            'minutes_before' => ['required', 'integer', 'min:1', 'max:525600'],
            'channel' => ['nullable', 'in:whatsapp,app,both'],
        ];

        if (! $update) {
            $rules['task_id'] = ['nullable', 'uuid', 'required_without:calendar_event_id'];
            $rules['calendar_event_id'] = ['nullable', 'uuid', 'required_without:task_id'];
        }

        $data = $request->validate($rules);
        $data['channel'] = $data['channel'] ?? 'whatsapp';

        return $data;
    }

    private function source(Request $request, array $data): array
    {
        if (isset($data['task_id'])) {
            return [Task::query()->where('user_id', $request->user()->id)->findOrFail($data['task_id']), 'task_id'];
        }

        return [CalendarEvent::query()->where('user_id', $request->user()->id)->findOrFail($data['calendar_event_id']), 'calendar_event_id'];
    }
}
