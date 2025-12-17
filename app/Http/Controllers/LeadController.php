<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    /**
     * Store a newly created lead
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'restaurant_name' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        $lead = Lead::create([
            ...$validated,
            'source' => 'landing_page',
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        Log::info('New lead created', ['lead_id' => $lead->id]);

        return response()->json([
            'success' => true,
            'message' => 'Obrigado pelo seu interesse! Entraremos em contato em breve.',
        ], 201);
    }
}
