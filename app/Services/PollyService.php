<?php

namespace App\Services;

use Aws\Polly\PollyClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Storage;

class PollyService
{
    private PollyClient $client;

    public function __construct()
    {
        $credentials = [
            'key' => config('services.aws.key'),
            'secret' => config('services.aws.secret'),
        ];
        
        if (config('services.aws.token')) {
            $credentials['token'] = config('services.aws.token');
        }

        $this->client = new PollyClient([
            'version' => 'latest',
            'region' => config('services.aws.region', 'eu-central-1'),
            'credentials' => $credentials,
        ]);
    }

    /**
     * Genera audio da testo usando Amazon Polly
     */
    public function synthesize(
        string $text,
        string $voiceId = 'Bianca',
        string $engine = 'generative',
        string $langCode = 'it-IT'
    ): array {
        if (config('services.mascot.mock')) {
            return [
                'success' => true,
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', // Placeholder audio
                'characters' => mb_strlen($text),
                'cached' => false,
                'mock' => true,
            ];
        }

        // Cache basata su hash del contenuto
        $hash = md5($text . $voiceId . $engine . $langCode);
        $filename = "audio/tts/{$hash}.mp3";

        if (Storage::disk('public')->exists($filename)) {
            return [
                'success' => true,
                'audio_url' => "storage/{$filename}",
                'characters' => 0,
                'cached' => true,
            ];
        }

        $charCount = mb_strlen($text);
        if ($charCount > 3000) {
            return [
                'success' => false,
                'error' => "Testo supera il limite di 3000 caratteri ({$charCount})",
            ];
        }

        try {
            $result = $this->client->synthesizeSpeech([
                'Text' => $text,
                'VoiceId' => $voiceId,
                'Engine' => $engine,
                'OutputFormat' => 'mp3',
                'LanguageCode' => $langCode,
            ]);

            $audioContent = $result->get('AudioStream')->getContents();
            Storage::disk('public')->put($filename, $audioContent);

            return [
                'success' => true,
                'audio_url' => "storage/{$filename}",
                'characters' => $charCount,
                'cached' => false,
            ];

        } catch (AwsException $e) {
            report($e);

            $awsError = $e->getAwsErrorCode();
            $errorMessage = match ($awsError) {
                'InvalidParameterValue' => 'Parametri non validi (voce/engine non compatibili)',
                'TextLengthExceededException' => 'Testo troppo lungo',
                'ServiceFailure' => 'Errore servizio AWS',
                default => $e->getAwsErrorMessage() ?? $e->getMessage(),
            };

            return [
                'success' => false,
                'error' => $errorMessage,
                'aws_error_code' => $awsError,
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
     * Restituisce le voci disponibili per una lingua
     */
    public function getVoices(string $langCode = 'it-IT'): array
    {
        if (config('services.mascot.mock')) {
            return [
                'success' => true,
                'voices' => [
                    ['id' => 'MockVoice', 'name' => 'Voce Demo', 'gender' => 'Neutral', 'engines' => ['standard', 'neural']]
                ],
                'mock' => true
            ];
        }

        try {
            $result = $this->client->describeVoices([
                'LanguageCode' => $langCode,
            ]);

            $voices = [];
            foreach ($result->get('Voices') as $voice) {
                $voices[] = [
                    'id' => $voice['Id'],
                    'name' => $voice['Name'],
                    'gender' => $voice['Gender'],
                    'engines' => $voice['SupportedEngines'],
                ];
            }

            return [
                'success' => true,
                'voices' => $voices,
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
