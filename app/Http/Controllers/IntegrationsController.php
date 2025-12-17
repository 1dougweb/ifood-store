<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    /**
     * Display the integrations page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get restaurants the user can manage
        $restaurants = $user->restaurants()
            ->select('id', 'name', 'ifood_merchant_id', 'ifood_access_token', 'whatsapp_number')
            ->get()
            ->merge($user->managedRestaurants()
                ->select('id', 'name', 'ifood_merchant_id', 'ifood_access_token', 'whatsapp_number')
                ->get());

        return Inertia::render('Integrations', [
            'restaurants' => $restaurants->unique('id')->values(),
            'evolutionApiUrl' => config('services.evolution_api.url'),
            'webhookUrl' => config('app.url') . '/api/webhooks/ifood',
        ]);
    }
}
