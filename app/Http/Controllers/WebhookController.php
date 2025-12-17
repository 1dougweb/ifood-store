<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIfoodWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle iFood webhook
     */
    public function handleIfood(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // Log webhook received
            Log::info('iFood webhook received', [
                'event' => $data['event'] ?? 'unknown',
                'data' => $data,
            ]);

            // Validate webhook signature if iFood provides one
            // if (!$this->validateSignature($request)) {
            //     return response()->json(['error' => 'Invalid signature'], 401);
            // }

            // Dispatch job to process webhook asynchronously
            ProcessIfoodWebhook::dispatch($data);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('Error handling iFood webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Validate webhook signature (implement if iFood provides signature validation)
     */
    protected function validateSignature(Request $request): bool
    {
        // TODO: Implement signature validation if iFood provides it
        return true;
    }
}
