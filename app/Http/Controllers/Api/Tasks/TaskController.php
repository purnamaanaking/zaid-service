<?php

namespace App\Http\Controllers\Api\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\Tasks\TaskMutationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskMutationService $taskMutationService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Task::query()
            ->where('user_id', $request->user()->id)
            ->with(['recurrence', 'reminders']);

        if ($request->has('date')) {
            $query->whereDate('scheduled_date', $request->query('date'));
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('scheduled_date', [$request->query('from'), $request->query('to')]);
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->query('include_completed') === 'false') {
            $query->where('status', '!=', 'completed');
        }

        if ($request->has('search')) {
            $query->where('title', 'ilike', '%'.$request->query('search').'%');
        }

        $tasks = $query->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => TaskResource::collection($tasks),
                'meta' => ['total' => $tasks->count()],
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->ensureTaskListBelongsToUser($request, $data['task_list_id'] ?? null);

        $task = $this->taskMutationService->create(
            $request->user(),
            $data,
        );

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => ['task' => new TaskResource($task)],
        ], 201);
    }

    public function show(Request $request, string $taskId): JsonResponse
    {
        $task = Task::query()
            ->where('user_id', $request->user()->id)
            ->with(['recurrence', 'reminders'])
            ->findOrFail($taskId);

        return response()->json([
            'success' => true,
            'data' => ['task' => new TaskResource($task)],
        ]);
    }

    public function update(UpdateTaskRequest $request, string $taskId): JsonResponse
    {
        $task = Task::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($taskId);

        $data = $request->validated();
        $this->ensureTaskListBelongsToUser($request, $data['task_list_id'] ?? null);

        $task = $this->taskMutationService->update(
            $task,
            $request->user(),
            $data,
        );

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => ['task' => new TaskResource($task)],
        ]);
    }

    public function destroy(Request $request, string $taskId): JsonResponse
    {
        $task = Task::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($taskId);

        $this->taskMutationService->delete($task, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
            'data' => ['task_id' => $taskId],
        ]);
    }

    public function complete(Request $request, string $taskId): JsonResponse
    {
        $task = Task::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($taskId);

        $task = $this->taskMutationService->complete($task, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Task completed',
            'data' => ['task' => new TaskResource($task)],
        ]);
    }

    private function ensureTaskListBelongsToUser(Request $request, ?string $taskListId): void
    {
        if ($taskListId !== null && ! $request->user()->taskLists()->whereKey($taskListId)->exists()) {
            abort(422, 'Task list does not belong to the authenticated user.');
        }
    }

    public function restore(Request $request, string $taskId): JsonResponse
    {
        $task = Task::withTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($taskId);

        $task = $this->taskMutationService->restore($task, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Task restored',
            'data' => ['task' => new TaskResource($task)],
        ]);
    }
}
