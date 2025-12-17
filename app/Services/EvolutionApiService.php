<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instanceName;

    public function __construct()
    {
        $this->baseUrl = config('services.evolution_api.url');
        $this->apiKey = config('services.evolution_api.key');
        $this->instanceName = config('services.evolution_api.instance_name', 'default');
    }

    /**
     * Send a text message via WhatsApp
     *
     * @param string $phoneNumber Phone number in format: 5511999999999
     * @param string $message Message content
     * @return array|null Response data or null on failure
     */
    public function sendTextMessage(string $phoneNumber, string $message): ?array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", [
                'number' => $phoneNumber,
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phoneNumber,
                    'response' => $response->json(),
                ]);

                return $response->json();
            }

            Log::error('Failed to send WhatsApp message', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a template message (for future use)
     *
     * @param string $phoneNumber
     * @param string $templateName
     * @param array $parameters
     * @return array|null
     */
    public function sendTemplateMessage(string $phoneNumber, string $templateName, array $parameters = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->post("{$this->baseUrl}/message/sendTemplate/{$this->instanceName}", [
                'number' => $phoneNumber,
                'template' => $templateName,
                'parameters' => $parameters,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to send WhatsApp template message', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception sending WhatsApp template message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Format phone number to Evolution API format
     * Removes special characters and ensures country code
     *
     * @param string $phoneNumber
     * @return string
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phoneNumber);

        // If doesn't start with country code, assume Brazil (55)
        if (!str_starts_with($phone, '55') && strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}

