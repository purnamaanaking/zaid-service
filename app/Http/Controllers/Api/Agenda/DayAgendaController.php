<?php

namespace App\Http\Controllers\Api\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Services\Agenda\AgendaQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DayAgendaController extends Controller
{
    public function __invoke(Request $request, AgendaQueryService $agendaQueryService): JsonResponse
    {
        $request->validate(['date' => ['required', 'date', 'date_format:Y-m-d']]);

        $items = $agendaQueryService->dayAgenda($request->user(), $request->query('date'));

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $request->query('date'),
                'items' => TaskResource::collection($items),
            ],
        ]);
    }
}
