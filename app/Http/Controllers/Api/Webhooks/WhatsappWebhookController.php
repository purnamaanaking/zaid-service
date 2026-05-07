<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WhatsappWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.verify_token');

        if (
            $request->query('hub_mode') === 'subscribe'
            || $request->query('hub.mode') === 'subscribe'
        ) {
            $incomingToken = $request->query('hub_verify_token', $request->query('hub.verify_token'));

            if ($verifyToken !== null && hash_equals((string) $verifyToken, (string) $incomingToken)) {
                return response((string) $request->query('hub_challenge', $request->query('hub.challenge')), 200);
            }
        }

        return response('Invalid verify token.', 403);
    }

    public function handle(Request $request, WhatsappWebhookService $webhookService): JsonResponse
    {
        $webhookService->handleInbound($request->all());

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp webhook accepted.',
        ], 202);
    }
}
