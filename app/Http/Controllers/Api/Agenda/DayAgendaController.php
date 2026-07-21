<?php

namespace App\Http\Controllers\Api\Agenda;

use App\Http\Controllers\Controller;
use App\Services\Agenda\AgendaQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DayAgendaController extends Controller
{
    public function __invoke(Request $request, AgendaQueryService $agenda): JsonResponse
    {
        $request->validate(['date' => ['required', 'date', 'date_format:Y-m-d']]);
        $items = $agenda->dayAgenda($request->user(), $request->query('date'));

        return response()->json(['success' => true, 'data' => ['date' => $request->query('date'), 'items' => $items]]);
    }
}
