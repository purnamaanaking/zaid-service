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

    public function store(Request $request): JsonResponse
    {
        $request->validate(['text' => ['required', 'string']]);

        $result = $this->promptCommandService->process(
            $request->user(),
            $request->string('text')->toString(),
            'app_prompt',
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
