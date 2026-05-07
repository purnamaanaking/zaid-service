<?php

namespace App\Http\Controllers\Api\Whatsapp;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WahaApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WahaSessionController extends Controller
{
    public function status(WahaApiService $wahaApiService): JsonResponse
    {
        $result = $wahaApiService->getSession();

        return response()->json([
            'success' => $result['ok'],
            'message' => 'WAHA session status fetched.',
            'data' => $result['data'],
        ], $result['ok'] ? 200 : 502);
    }

    public function start(Request $request, WahaApiService $wahaApiService): JsonResponse
    {
        $webhookUrl = $request->input('webhook_url')
            ?: rtrim((string) config('app.url'), '/').'/api/v1/webhooks/whatsapp';

        $result = $wahaApiService->createOrStartSession($webhookUrl);

        return response()->json([
            'success' => $result['ok'],
            'message' => 'WAHA session create/start requested.',
            'data' => $result['data'],
        ], $result['ok'] ? 200 : 502);
    }

    public function qr(WahaApiService $wahaApiService): JsonResponse
    {
        $result = $wahaApiService->getQr();

        return response()->json([
            'success' => $result['ok'],
            'message' => 'WAHA QR fetched.',
            'data' => $result['data'],
        ], $result['ok'] ? 200 : 502);
    }
}
