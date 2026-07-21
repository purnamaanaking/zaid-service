<?php

namespace App\Http\Controllers\Api\Reminders;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Reminder::query()->where('user_id', $request->user()->id)->whereIn('status', ['pending', 'sent', 'failed'])->with('calendarEvent')->orderBy('remind_at')->get();
        return response()->json(['success' => true, 'data' => ['items' => $items]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $event = CalendarEvent::query()->where('user_id', $request->user()->id)->findOrFail($data['calendar_event_id']);
        $reminder = Reminder::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'calendar_event_id' => $event->id, 'minutes_before' => $data['minutes_before'], 'channel' => $data['channel']],
            ['remind_at' => $event->starts_at->copy()->subMinutes($data['minutes_before']), 'status' => 'pending', 'sent_at' => null, 'error_message' => null],
        );
        return response()->json(['success' => true, 'data' => ['reminder' => $reminder]], 201);
    }

    public function update(Request $request, string $reminderId): JsonResponse
    {
        $reminder = Reminder::query()->where('user_id', $request->user()->id)->with('calendarEvent')->findOrFail($reminderId);
        $data = $this->validated($request, true);
        if (! $reminder->calendarEvent) throw ValidationException::withMessages(['reminder' => 'Event tidak ditemukan.']);
        $reminder->update(['minutes_before' => $data['minutes_before'], 'channel' => $data['channel'], 'remind_at' => $reminder->calendarEvent->starts_at->copy()->subMinutes($data['minutes_before']), 'status' => 'pending', 'sent_at' => null, 'error_message' => null]);
        return response()->json(['success' => true, 'data' => ['reminder' => $reminder->fresh()]]);
    }

    public function destroy(Request $request, string $reminderId): JsonResponse
    {
        Reminder::query()->where('user_id', $request->user()->id)->findOrFail($reminderId)->delete();
        return response()->json(['success' => true]);
    }

    private function validated(Request $request, bool $update = false): array
    {
        $rules = ['minutes_before' => ['required', 'integer', 'min:1', 'max:525600'], 'channel' => ['nullable', 'in:whatsapp,app,both']];
        if (! $update) $rules['calendar_event_id'] = ['required', 'uuid'];
        $data = $request->validate($rules);
        $data['channel'] ??= 'whatsapp';
        return $data;
    }
}
