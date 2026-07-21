<?php

namespace App\Http\Controllers\Api\Prompts;

use App\Http\Controllers\Controller;
use App\Models\PromptRequest;
use App\Services\Prompt\PromptCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function __construct(private readonly PromptCommandService $promptCommandService) {}

    public function index(Request $request): JsonResponse
    {
        $items = PromptRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'app_prompt')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (PromptRequest $prompt) => [
                'id' => $prompt->id,
                'text' => $prompt->raw_text,
                'response' => data_get($prompt->execution_summary, 'human_response'),
                'items' => data_get($prompt->execution_summary, 'items', []),
                'status' => $prompt->execution_status,
                'created_at' => $prompt->created_at?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => ['items' => $items]]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        PromptRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'app_prompt')
            ->delete();

        return response()->json(['success' => true]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'text' => ['required', 'string'],
            'selected_date' => ['nullable', 'date_format:Y-m-d'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.type' => ['required_with:attachments', 'string', 'in:image,audio_transcription'],
            'attachments.*.url' => ['nullable', 'string'],
            'attachments.*.text' => ['nullable', 'string'],
        ]);

        $result = $this->promptCommandService->process(
            $request->user(),
            $request->string('text')->toString(),
            'app_prompt',
            $request->input('attachments'),
            $request->string('selected_date')->toString() ?: null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Prompt processed successfully',
            'data' => $result,
        ]);
    }

    public function show(Request $request, string $promptRequestId): JsonResponse
    {
        $promptRequest = PromptRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('actions')
            ->findOrFail($promptRequestId);

        return response()->json([
            'success' => true,
            'data' => $promptRequest,
        ]);
    }

    public function confirm(Request $request, string $promptRequestId): JsonResponse
    {
        $request->validate(['confirmed' => ['required', 'boolean']]);

        $promptRequest = PromptRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('execution_status', 'awaiting_confirmation')
            ->findOrFail($promptRequestId);

        $result = $this->promptCommandService->confirm(
            $promptRequest,
            $request->user(),
            $request->boolean('confirmed'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Prompt action confirmed',
            'data' => $result,
        ]);
    }
}
