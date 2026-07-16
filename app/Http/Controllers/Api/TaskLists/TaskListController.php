<?php

namespace App\Http\Controllers\Api\TaskLists;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lists = $request->user()->taskLists()->withCount('tasks')->orderBy('position')->get();

        return response()->json(['success' => true, 'data' => ['items' => $lists]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'color' => ['nullable', 'string', 'max:20']]);
        $data['position'] = (int) $request->user()->taskLists()->max('position') + 1;
        $list = $request->user()->taskLists()->create($data);

        return response()->json(['success' => true, 'data' => ['task_list' => $list]], 201);
    }

    public function update(Request $request, string $taskListId): JsonResponse
    {
        $list = $request->user()->taskLists()->findOrFail($taskListId);
        $list->update($request->validate(['name' => ['sometimes', 'string', 'max:100'], 'color' => ['nullable', 'string', 'max:20'], 'position' => ['sometimes', 'integer', 'min:0']]));

        return response()->json(['success' => true, 'data' => ['task_list' => $list->fresh()]]);
    }

    public function destroy(Request $request, string $taskListId): JsonResponse
    {
        $request->user()->taskLists()->findOrFail($taskListId)->delete();

        return response()->json(['success' => true]);
    }
}
