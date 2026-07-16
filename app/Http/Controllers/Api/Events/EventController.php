<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);

        $items = $request->user()->calendarEvents()
            ->whereDate('starts_at', '<=', $request->date('to'))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $request->date('from')))
            ->orderBy('starts_at')
            ->get();

        return response()->json(['success' => true, 'data' => ['items' => $items]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateEvent($request);
        $event = $request->user()->calendarEvents()->create($data);

        return response()->json(['success' => true, 'data' => ['event' => $event]], 201);
    }

    public function update(Request $request, string $eventId): JsonResponse
    {
        $event = $request->user()->calendarEvents()->findOrFail($eventId);
        $event->update($this->validateEvent($request, true));

        return response()->json(['success' => true, 'data' => ['event' => $event->fresh()]]);
    }

    public function destroy(Request $request, string $eventId): JsonResponse
    {
        $request->user()->calendarEvents()->findOrFail($eventId)->delete();

        return response()->json(['success' => true]);
    }

    private function validateEvent(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => [$partial ? 'sometimes' : 'required', 'nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'all_day' => ['nullable', 'boolean'],
        ]);
    }
}
