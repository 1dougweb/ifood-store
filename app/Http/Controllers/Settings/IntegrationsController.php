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
            'webhookUrl' => config('app.url') . '/api/webhooks/ifood',
        ]);
    }
}
