<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\IfoodService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantController extends Controller
{
    public function __construct(
        protected IfoodService $ifoodService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Clientes veem apenas seus restaurantes, gestores veem os que gerenciam
        if ($user->hasRole('cliente')) {
            $restaurants = $user->restaurants()
                ->withCount(['orders', 'notifications'])
                ->latest()
                ->paginate(10);
        } else {
            // Gestores e admins veem restaurantes que gerenciam + próprios
            $restaurants = Restaurant::whereHas('managers', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            })
                ->orWhere('user_id', $user->id)
                ->withCount(['orders', 'notifications'])
                ->latest()
                ->paginate(10);
        }

        return Inertia::render('Restaurants/Index', [
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Restaurants/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->user()->can('manage-restaurants') || abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'ifood_client_id' => 'nullable|string|max:255',
            'ifood_client_secret' => 'nullable|string|max:500',
            'notification_settings' => 'nullable|array',
        ]);

        $restaurant = $request->user()->restaurants()->create($validated);

        return redirect()->route('restaurants.index')
            ->with('success', __('messages.restaurant_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Restaurant $restaurant): Response
    {
        $user = $request->user();
        
        // Verificar se o usuário tem acesso a este restaurante
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            // Gestores podem ver se gerenciam ou são donos
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        $restaurant->load(['orders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Restaurants/Show', [
            'restaurant' => $restaurant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Restaurant $restaurant): Response
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        // Gerar URL do webhook
        $baseUrl = config('app.url');
        if (empty($baseUrl) || $baseUrl === 'http://localhost' || $baseUrl === 'http://127.0.0.1:8000') {
            // Usar URL da requisição atual como fallback
            $baseUrl = $request->getSchemeAndHttpHost();
        }
        
        $webhookUrl = rtrim($baseUrl, '/') . '/api/webhooks/ifood';
        
        // Log para debug (remover em produção)
        \Log::debug('Webhook URL generated', [
            'baseUrl' => $baseUrl,
            'webhookUrl' => $webhookUrl,
            'config_app_url' => config('app.url'),
        ]);
        
        return Inertia::render('Restaurants/Edit', [
            'restaurant' => $restaurant,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'ifood_client_id' => 'nullable|string|max:255',
            'ifood_client_secret' => 'nullable|string|max:500',
            'notification_settings' => 'nullable|array',
            'notification_settings.enabled_events' => 'nullable|array',
            'notification_settings.enabled_events.*' => 'string|in:new_order,delayed_order,delivered_order,cancelled_order',
            'notification_settings.quiet_hours' => 'nullable|array',
            'notification_settings.quiet_hours.enabled' => 'nullable|boolean',
            'notification_settings.quiet_hours.start' => 'nullable|string',
            'notification_settings.quiet_hours.end' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $restaurant->update($validated);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Restaurante atualizado com sucesso!',
            ]);
        }

        return redirect()->route('restaurants.index')
            ->with('success', __('messages.restaurant_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        abort_unless($user->can('manage-restaurants'), 403);
        abort_if(
            !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
            403
        );

        $restaurant->delete();

        return redirect()->route('restaurants.index')
            ->with('success', __('messages.restaurant_deleted'));
    }

    /**
     * Get user code for OTP-like authorization flow
     */
    public function getIfoodUserCode(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        try {
            $result = $this->ifoodService->getUserCode($restaurant);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'userCode' => $result['userCode'],
                    'verificationUrl' => $result['verificationUrl'],
                    'verificationUrlComplete' => $result['verificationUrlComplete'],
                    'expiresIn' => $result['expiresIn'],
                ]);
            }

            return response()->json([
                'error' => 'Erro ao obter código de autorização. Verifique se o Client ID está correto.',
            ], 400);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Unexpected error getting iFood user code', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro inesperado ao obter código de autorização: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exchange user code for access token
     */
    public function exchangeIfoodUserCode(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        try {
            $result = $this->ifoodService->exchangeUserCodeForToken($restaurant);
            
            if ($result) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Conta iFood conectada com sucesso!',
                    ]);
                }
                
                return redirect()->route('restaurants.edit', $restaurant)
                    ->with('success', 'Conta iFood conectada com sucesso!');
            }

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Código ainda não autorizado. Por favor, acesse a URL e autorize o código.',
                ], 400);
            }

            return redirect()->route('restaurants.edit', $restaurant)
                ->with('error', 'Código ainda não autorizado. Por favor, acesse a URL e autorize o código.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                ], 400);
            }

            return redirect()->route('restaurants.edit', $restaurant)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Connect to iFood using client credentials flow (for centralized applications)
     */
    public function connectIfood(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        try {
            $result = $this->ifoodService->getAccessTokenWithClientCredentials($restaurant);
            
            if ($result) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Conta iFood conectada com sucesso!',
                    ]);
                }
                
                return redirect()->route('restaurants.edit', $restaurant)
                    ->with('success', 'Conta iFood conectada com sucesso!');
            }

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Erro ao conectar com iFood. Verifique se o Client ID e Client Secret estão corretos.',
                ], 400);
            }

            return redirect()->route('restaurants.edit', $restaurant)
                ->with('error', 'Erro ao conectar com iFood. Verifique se o Client ID e Client Secret estão corretos.');
        } catch (\RuntimeException $e) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage(),
                ], 400);
            }

            return redirect()->route('restaurants.edit', $restaurant)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get OAuth authorization URL for iFood (for distributed applications)
     * 
     * @deprecated Use connectIfood for centralized applications
     */
    public function getIfoodAuthUrl(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        try {
            $url = $this->ifoodService->getAuthorizationUrl($restaurant);
            $redirectUri = $this->ifoodService->getRedirectUri($restaurant);
            
            return response()->json([
                'url' => $url,
                'redirect_uri' => $redirectUri,
                'message' => 'Certifique-se de que o redirect_uri está registrado no portal do desenvolvedor do iFood: ' . $redirectUri,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'redirect_uri' => $this->ifoodService->getRedirectUri($restaurant) ?? 'N/A',
            ], 400);
        }
    }

    /**
     * Handle iFood OAuth callback
     */
    public function handleIfoodCallback(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        
        // Verificar permissão
        if ($user->hasRole('cliente')) {
            abort_if($restaurant->user_id !== $user->id, 403);
        } else {
            abort_unless($user->can('manage-restaurants'), 403);
            abort_if(
                !$restaurant->managers->contains($user) && $restaurant->user_id !== $user->id,
                403
            );
        }

        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('restaurants.edit', $restaurant)
                ->with('error', 'Código de autorização não encontrado.');
        }

        $result = $this->ifoodService->exchangeCodeForToken($code, $restaurant);

        if ($result) {
            // Get merchant info
            $merchant = $this->ifoodService->getMerchant($restaurant);
            if ($merchant && isset($merchant['id'])) {
                $restaurant->update(['ifood_merchant_id' => $merchant['id']]);
            }

            return redirect()->route('restaurants.edit', $restaurant)
                ->with('success', 'Conta iFood conectada com sucesso!');
        }

        return redirect()->route('restaurants.edit', $restaurant)
            ->with('error', 'Erro ao conectar conta iFood.');
    }
}
