<?php

namespace App\Http\Controllers\Api\Calendar;

use App\Http\Controllers\Controller;
use App\Services\Agenda\AgendaQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonthCalendarController extends Controller
{
    public function __invoke(Request $request, AgendaQueryService $agendaQueryService): JsonResponse
    {
        $request->validate(['month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']]);

        $days = $agendaQueryService->monthSummary($request->user(), $request->query('month'));

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $request->query('month'),
                'days' => $days,
            ],
        ]);
    }
}
