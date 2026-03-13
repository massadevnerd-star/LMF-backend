<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DeepLService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepl.api_key');
        $this->apiUrl = config('services.deepl.api_url');
    }

    /**
     * Traduce un testo usando DeepL API
     */
    public function translate(
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): array {
        if (config('services.mascot.mock')) {
            return [
                'success' => true,
                'text' => "[{$targetLang}] " . $text,
                'detected_source_lang' => $sourceLang ?? 'IT',
                'characters' => mb_strlen($text),
                'cached' => false,
                'mock' => true,
            ];
        }

        // Cache per evitare chiamate duplicate
        $cacheKey = 'deepl_' . md5($text . $targetLang . $sourceLang);

        if ($cached = Cache::get($cacheKey)) {
            return [
                'success' => true,
                'text' => $cached['text'],
                'detected_source_lang' => $cached['detected_source_lang'],
                'characters' => 0,
                'cached' => true,
            ];
        }

        try {
            $payload = [
                'text' => [$text],
                'target_lang' => strtoupper($targetLang),
            ];

            if ($sourceLang) {
                $payload['source_lang'] = strtoupper($sourceLang);
            }

            $response = Http::withHeaders([
                'Authorization' => "DeepL-Auth-Key {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/translate", $payload);

            if ($response->failed()) {
                $status = $response->status();
                $errorMessage = match ($status) {
                    401 => 'API key non valida',
                    403 => 'Accesso negato',
                    456 => 'Limite caratteri mensile raggiunto',
                    429 => 'Troppe richieste, riprova più tardi',
                    default => "Errore HTTP {$status}",
                };

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'http_status' => $status,
                    'details' => $response->json(),
                ];
            }

            $data = $response->json();
            $translation = $data['translations'][0];

            // Cache per 30 giorni
            Cache::put($cacheKey, [
                'text' => $translation['text'],
                'detected_source_lang' => $translation['detected_source_language'],
            ], now()->addDays(30));

            return [
                'success' => true,
                'text' => $translation['text'],
                'detected_source_lang' => $translation['detected_source_language'],
                'characters' => mb_strlen($text),
                'cached' => false,
            ];

        } catch (\Exception $e) {
            report($e);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica l'utilizzo corrente dell'account DeepL
     */
    public function getUsage(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "DeepL-Auth-Key {$this->apiKey}",
            ])->get("{$this->apiUrl}/usage");

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}",
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'character_count' => $data['character_count'],
                'character_limit' => $data['character_limit'],
                'remaining' => $data['character_limit'] - $data['character_count'],
            ];

        } catch (\Exception $e) {
            report($e);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
