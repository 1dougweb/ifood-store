<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurants = $request->user()->restaurants()->get();

        return Inertia::render('settings/Integrations', [
            'restaurants' => $restaurants,
            'evolutionApiUrl' => config('services.evolution_api.url'),
            'webhookUrl' => $this->getWebhookUrl($request),
        ]);
    }

    /**
     * Get webhook URL dynamically using APP_URL
     */
    private function getWebhookUrl(Request $request): string
    {
        $baseUrl = env('APP_URL');
        
        // Se APP_URL não estiver definido ou for localhost, usar a URL da requisição atual
        if (empty($baseUrl) || $baseUrl === 'http://localhost' || $baseUrl === 'http://127.0.0.1:8000') {
            $baseUrl = $request->getSchemeAndHttpHost();
        }
        
        // Garantir que use HTTPS se a requisição for HTTPS ou se APP_URL já for HTTPS
        if ($request->secure() || $request->header('X-Forwarded-Proto') === 'https' || str_starts_with($baseUrl, 'https://')) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }
        
        return rtrim($baseUrl, '/') . '/api/webhooks/ifood';
    }
}
