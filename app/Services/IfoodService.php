<?php

namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IfoodService
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $redirectUri;

    public function __construct()
    {
        $this->baseUrl = config('services.ifood.base_url', 'https://merchant-api.ifood.com.br');
        $this->clientId = config('services.ifood.client_id') ?: null;
        $this->clientSecret = config('services.ifood.client_secret') ?: null;
        $this->redirectUri = config('services.ifood.redirect_uri') ?: null;
    }

    /**
     * Check if iFood service is configured
     *
     * @return bool
     */
    protected function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }

    /**
     * Ensure iFood service is configured before use
     *
     * @throws \RuntimeException
     */
    protected function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException(
                'iFood service configuration is incomplete. Please set IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET, and IFOOD_REDIRECT_URI environment variables in your .env file.'
            );
        }
    }

    /**
     * Request a user code for OTP-like authorization flow
     *
     * @param Restaurant $restaurant
     * @return array|null
     */
    public function getUserCode(Restaurant $restaurant): ?array
    {
        // Use restaurant-specific credentials if available, otherwise fallback to global
        $clientId = $restaurant->ifood_client_id ?: $this->clientId;

        if (!$clientId) {
            throw new \RuntimeException(
                'iFood Client ID not configured for this restaurant. Please configure IFOOD_CLIENT_ID in the restaurant settings.'
            );
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->asForm()->post('https://merchant-api.ifood.com.br/authentication/v1.0/oauth/userCode', [
                'clientId' => $clientId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store the authorizationCodeVerifier temporarily (we'll need it later)
                $restaurant->update([
                    'ifood_authorization_code_verifier' => $data['authorizationCodeVerifier'] ?? null,
                    'ifood_user_code_expires_at' => now()->addSeconds($data['expiresIn'] ?? 600),
                ]);

                Log::info('Successfully obtained iFood user code', [
                    'restaurant_id' => $restaurant->id,
                    'user_code' => $data['userCode'] ?? null,
                ]);

                return $data;
            }

            $errorBody = $response->json();
            
            // Extrair mensagem de erro corretamente (pode estar em error.message ou error diretamente)
            $errorMessage = null;
            if (isset($errorBody['error'])) {
                if (is_array($errorBody['error'])) {
                    $errorMessage = $errorBody['error']['message'] ?? $errorBody['error']['code'] ?? 'Erro desconhecido';
                } else {
                    $errorMessage = $errorBody['error'];
                }
            } else {
                $errorMessage = $errorBody['message'] ?? $response->body();
            }

            // Mensagem mais amigável para erro de grant type não autorizado
            if (stripos($errorMessage, 'grant type not authorized') !== false) {
                $errorMessage = 'Grant type não autorizado para este Client ID. O fluxo de autorização por código (userCode) precisa estar habilitado no portal do desenvolvedor do iFood para este Client ID.';
            }

            Log::error('Failed to get iFood user code', [
                'restaurant_id' => $restaurant->id,
                'status' => $response->status(),
                'response' => $response->body(),
                'error_body' => $errorBody,
                'error_message' => $errorMessage,
            ]);

            throw new \RuntimeException($errorMessage);
        } catch (\RuntimeException $e) {
            // Relançar RuntimeException para que o controller possa capturar
            throw $e;
        } catch (\Exception $e) {
            Log::error('Exception getting iFood user code', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Erro inesperado ao obter código de autorização: ' . $e->getMessage());
        }
    }

    /**
     * Exchange authorization code verifier for access token
     *
     * @param Restaurant $restaurant
     * @return array|null
     */
    public function exchangeUserCodeForToken(Restaurant $restaurant): ?array
    {
        // Use restaurant-specific credentials if available, otherwise fallback to global
        $clientId = $restaurant->ifood_client_id ?: $this->clientId;
        $clientSecret = $restaurant->ifood_client_secret ?: $this->clientSecret;
        $authorizationCodeVerifier = $restaurant->ifood_authorization_code_verifier;

        if (!$clientId || !$clientSecret || !$authorizationCodeVerifier) {
            throw new \RuntimeException(
                'iFood credentials or authorization code verifier not configured for this restaurant.'
            );
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->asForm()->post('https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token', [
                'grantType' => 'authorization_code_verifier',
                'authorizationCodeVerifier' => $authorizationCodeVerifier,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $restaurant->update([
                    'ifood_access_token' => $data['access_token'] ?? null,
                    'ifood_refresh_token' => $data['refresh_token'] ?? null,
                    'ifood_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                    'ifood_authorization_code_verifier' => null, // Clear after successful exchange
                ]);

                // Get merchant info after getting token
                $merchant = $this->getMerchant($restaurant);
                if ($merchant && isset($merchant['id'])) {
                    $restaurant->update(['ifood_merchant_id' => $merchant['id']]);
                }

                Log::info('Successfully exchanged user code for iFood access token', [
                    'restaurant_id' => $restaurant->id,
                ]);

                return $data;
            }

            Log::error('Failed to exchange user code for iFood access token', [
                'restaurant_id' => $restaurant->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception exchanging user code for iFood access token', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get access token using client credentials flow (for centralized applications)
     *
     * @param Restaurant $restaurant
     * @return array|null
     */
    public function getAccessTokenWithClientCredentials(Restaurant $restaurant): ?array
    {
        // Use restaurant-specific credentials if available, otherwise fallback to global
        $clientId = $restaurant->ifood_client_id ?: $this->clientId;
        $clientSecret = $restaurant->ifood_client_secret ?: $this->clientSecret;

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException(
                'iFood credentials not configured for this restaurant. Please configure IFOOD_CLIENT_ID and IFOOD_CLIENT_SECRET in the restaurant settings.'
            );
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->asForm()->post('https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token', [
                'grantType' => 'client_credentials',
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $restaurant->update([
                    'ifood_access_token' => $data['access_token'],
                    'ifood_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                // Get merchant info after getting token
                $merchant = $this->getMerchant($restaurant);
                if ($merchant && isset($merchant['id'])) {
                    $restaurant->update(['ifood_merchant_id' => $merchant['id']]);
                }

                Log::info('Successfully obtained iFood access token via client credentials', [
                    'restaurant_id' => $restaurant->id,
                ]);

                return $data;
            }

            Log::error('Failed to get iFood access token via client credentials', [
                'restaurant_id' => $restaurant->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting iFood access token via client credentials', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get OAuth authorization URL (for distributed applications)
     * 
     * @deprecated Use getAccessTokenWithClientCredentials for centralized applications
     * @param Restaurant $restaurant
     * @return string
     */
    public function getAuthorizationUrl(Restaurant $restaurant): string
    {
        // Use restaurant-specific credentials if available, otherwise fallback to global
        $clientId = $restaurant->ifood_client_id ?: $this->clientId;
        $redirectUri = $this->getRedirectUri($restaurant);

        if (!$clientId || !$redirectUri) {
            throw new \RuntimeException(
                'iFood credentials not configured for this restaurant. Please configure IFOOD_CLIENT_ID and IFOOD_CLIENT_SECRET in the restaurant settings or set global IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET, and IFOOD_REDIRECT_URI environment variables.'
            );
        }

        // Validar que o redirect_uri está configurado corretamente
        $baseUrl = config('app.url');
        if (empty($baseUrl) || $baseUrl === 'http://localhost') {
            Log::warning('APP_URL not configured properly, using request URL', [
                'restaurant_id' => $restaurant->id,
                'redirect_uri' => $redirectUri,
            ]);
        }

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'MERCHANT_ORDERS',
            'state' => (string) $restaurant->id,
        ]);

        $authUrl = "https://portal.ifood.com.br/oauth/authorize?{$params}";
        
        Log::info('Generated iFood authorization URL', [
            'restaurant_id' => $restaurant->id,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'auth_url' => $authUrl,
        ]);

        return $authUrl;
    }

    /**
     * Get redirect URI for a restaurant
     *
     * @param Restaurant $restaurant
     * @return string
     */
    public function getRedirectUri(Restaurant $restaurant): string
    {
        $baseUrl = config('app.url');
        
        // Se APP_URL não estiver configurado ou for localhost, usar a URL da requisição
        if (empty($baseUrl) || $baseUrl === 'http://localhost' || $baseUrl === 'http://127.0.0.1:8000') {
            // Em produção, isso não deve acontecer, mas em desenvolvimento podemos usar a URL atual
            $baseUrl = request()->getSchemeAndHttpHost();
        }
        
        $redirectUri = rtrim($baseUrl, '/') . "/restaurants/{$restaurant->id}/ifood/callback";
        
        return $redirectUri;
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @param Restaurant $restaurant
     * @return array|null
     */
    public function exchangeCodeForToken(string $code, Restaurant $restaurant): ?array
    {
        // Use restaurant-specific credentials if available, otherwise fallback to global
        $clientId = $restaurant->ifood_client_id ?: $this->clientId;
        $clientSecret = $restaurant->ifood_client_secret ?: $this->clientSecret;
        $redirectUri = $this->getRedirectUri($restaurant);

        if (!$clientId || !$clientSecret || !$redirectUri) {
            throw new \RuntimeException(
                'iFood credentials not configured for this restaurant.'
            );
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->asForm()->post('https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token', [
                'grantType' => 'authorization_code',
                'authorizationCode' => $code,
                'redirectUri' => $redirectUri,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $restaurant->update([
                    'ifood_access_token' => $data['access_token'],
                    'ifood_refresh_token' => $data['refresh_token'] ?? null,
                    'ifood_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                return $data;
            }

            Log::error('Failed to exchange iFood authorization code', [
                'restaurant_id' => $restaurant->id,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception exchanging iFood authorization code', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refresh access token
     *
     * @param Restaurant $restaurant
     * @return bool
     */
    public function refreshToken(Restaurant $restaurant): bool
    {
        if (!$restaurant->ifood_refresh_token) {
            return false;
        }

        // Use restaurant-specific credentials if available, otherwise fallback to global
        $clientId = $restaurant->ifood_client_id ?: $this->clientId;
        $clientSecret = $restaurant->ifood_client_secret ?: $this->clientSecret;

        if (!$clientId || !$clientSecret) {
            Log::error('iFood credentials not configured for restaurant', [
                'restaurant_id' => $restaurant->id,
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->asForm()->post('https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token', [
                'grantType' => 'refresh_token',
                'refreshToken' => $restaurant->ifood_refresh_token,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $restaurant->update([
                    'ifood_access_token' => $data['access_token'],
                    'ifood_refresh_token' => $data['refresh_token'] ?? $restaurant->ifood_refresh_token,
                    'ifood_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);

                return true;
            }

            Log::error('Failed to refresh iFood token', [
                'restaurant_id' => $restaurant->id,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception refreshing iFood token', [
                'restaurant_id' => $restaurant->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get valid access token (refresh if needed)
     *
     * @param Restaurant $restaurant
     * @return string|null
     */
    public function getValidAccessToken(Restaurant $restaurant): ?string
    {
        if (!$restaurant->ifood_access_token) {
            return null;
        }

        // Check if token is expired or will expire in the next 5 minutes
        if ($restaurant->ifood_token_expires_at && $restaurant->ifood_token_expires_at->isBefore(now()->addMinutes(5))) {
            if (!$this->refreshToken($restaurant)) {
                return null;
            }
        }

        return $restaurant->fresh()->ifood_access_token;
    }

    /**
     * Make authenticated request to iFood API
     *
     * @param Restaurant $restaurant
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array|null
     */
    public function makeRequest(Restaurant $restaurant, string $method, string $endpoint, array $data = []): ?array
    {
        $token = $this->getValidAccessToken($restaurant);

        if (!$token) {
            Log::error('No valid access token for restaurant', [
                'restaurant_id' => $restaurant->id,
            ]);

            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ])->{strtolower($method)}("{$this->baseUrl}{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            // If unauthorized, try refreshing token once
            if ($response->status() === 401) {
                if ($this->refreshToken($restaurant)) {
                    $token = $restaurant->fresh()->ifood_access_token;
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json',
                    ])->{strtolower($method)}("{$this->baseUrl}{$endpoint}", $data);
                }

                if ($response->successful()) {
                    return $response->json();
                }
            }

            Log::error('iFood API request failed', [
                'restaurant_id' => $restaurant->id,
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception making iFood API request', [
                'restaurant_id' => $restaurant->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get order details from iFood
     *
     * @param Restaurant $restaurant
     * @param string $orderId
     * @return array|null
     */
    public function getOrder(Restaurant $restaurant, string $orderId): ?array
    {
        return $this->makeRequest($restaurant, 'GET', "/order/v1.0/orders/{$orderId}");
    }

    /**
     * Get merchant information
     *
     * @param Restaurant $restaurant
     * @return array|null
     */
    public function getMerchant(Restaurant $restaurant): ?array
    {
        return $this->makeRequest($restaurant, 'GET', '/merchant/v1.0/merchants/me');
    }
}

