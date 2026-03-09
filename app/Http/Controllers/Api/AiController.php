<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    /**
     * Generate a story using OpenRouter (Google Gemini).
     */
    public function generateStory(Request $request)
    {
        $request->validate([
            'storySubject' => 'required_without:prompt|string',
            'storyType' => 'nullable|string',
            'ageGroup' => 'nullable|string',
            'imageStyle' => 'nullable|string',
            'prompt' => 'nullable|string',
        ]);

        $openRouterKey = env('OPENROUTER_API_KEY');
        if (!$openRouterKey) {
            return response()->json(['error' => 'API Key not configured'], 500);
        }

        $jsonStructure = '
        {
          "title": "Story Title",
          "cover_image_prompt": "Detailed description for cover...",
          "chapters": [
            {
              "chapter_number": 1,
              "chapter_title": "Chapter Title",
              "text": "Story text...",
              "image_prompt": "Detailed description for chapter image..."
            }
          ]
        }';

        $systemPrompt = "You are a creative story writer for children.
        IMPORTANT: You must return the result strictly in valid JSON format matching this structure:
        $jsonStructure
        Do not include any markdown formatting like ```json ... ```, just the raw JSON object.";

        if ($request->has('prompt') && $request->prompt) {
            $userPrompt = $request->prompt;
        } else {
            $userPrompt = "
            Create a kids story based on the validation description:
            Target Audience: {$request->ageGroup} years old kids
            Type: {$request->storyType}
            Style: {$request->imageStyle}
            Subject: {$request->storySubject}

            Requisiti:
                Forniscimi esattamente 5 capitoli.
                Per ogni capitolo, fornisci un prompt testuale dettagliato per l'immagine adatto a un generatore di immagini AI (come Midjourney/DALL-E) nello stile di {$request->imageStyle}.
                Fornisci un prompt immagine per la copertina del libro con il titolo della storia.
                Restituisci il risultato rigorosamente in formato JSON valido.
            ";
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openRouterKey,
                'HTTP-Referer' => env('APP_URL'),
                'X-Title' => env('APP_NAME'),
                'Content-Type' => 'application/json',
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => 'google/gemini-2.0-flash-001',
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                    ]);

            if ($response->failed()) {
                Log::error('OpenRouter API Error', ['body' => $response->body()]);
                return response()->json(['error' => 'Failed to communicate with AI provider'], 500);
            }

            $content = $response->json('choices.0.message.content');
            if (!$content) {
                return response()->json(['error' => 'No content generated'], 500);
            }

            // Clean up Markdown if present
            $cleanJson = preg_replace('/```json\n?|```/', '', $content);
            $storyData = json_decode($cleanJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error', ['content' => $content, 'error' => json_last_error_msg()]);
                return response()->json(['error' => 'Failed to parse AI response'], 500);
            }

            return response()->json(['success' => true, 'data' => $storyData]);

        } catch (\Exception $e) {
            Log::error('Story Generation Exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate an image using Replicate.
     */
    public function generateImage(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $replicateToken = env('REPLICATE_API_TOKEN');
        if (!$replicateToken) {
            return response()->json(['error' => 'Replicate Token not configured'], 500);
        }

        try {
            // Using black-forest-labs/flux-schnell model as seen in frontend
            $response = Http::withToken($replicateToken)
                ->post('https://api.replicate.com/v1/predictions', [
                    'version' => 'black-forest-labs/flux-schnell', // Or use exact version hash if needed, but model name often works for latest
                    // Actually Replicate API usually usually takes "input" object
                    // Check if we need a specific version. 
                    // Let's use the standard endpoint for creating a prediction.
                    // Ideally we should find the model version, but "models/black-forest-labs/flux-schnell/predictions" might work.
                    // Let's try the model endpoint directly if possible, or use a known version.
                    // For safety, I'll use the model endpoint structure.
                    'input' => [
                        'prompt' => $request->prompt,
                        'output_format' => 'png',
                        'output_quality' => 80,
                        'aspect_ratio' => '1:1',
                        'num_outputs' => 1,
                        'width' => 1024,
                        'height' => 1024,
                        'num_inference_steps' => 4,
                        'guidance_scale' => 7.5,
                        'scheduler' => 'normal',
                    ]
                ]);

            // Replicate API changes: 
            // If we use https://api.replicate.com/v1/models/black-forest-labs/flux-schnell/predictions it might be better
            // But let's refine this to be robust. 
            // The frontend code used `replicate.run("black-forest-labs/flux-schnell", ...)` which waits.
            // HTTP API is async by default unless we poll.
            // BUT "flux-schnell" is... schnell (fast). Maybe we can wait?
            // Replicate has a "wait" header? prefer: wait

            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $replicateToken,
                'Content-Type' => 'application/json',
                'Prefer' => 'wait' // Try to wait for the result
            ])->post('https://api.replicate.com/v1/models/black-forest-labs/flux-schnell/predictions', [
                        'input' => [
                            'prompt' => $request->prompt,
                            'output_format' => 'png',
                            'aspect_ratio' => '1:1'
                        ]
                    ]);

            if ($response->failed()) {
                Log::error('Replicate API Error', ['body' => $response->body()]);
                return response()->json(['error' => 'Image generation failed'], 500);
            }

            $prediction = $response->json();

            if ($prediction['status'] === 'succeeded') {
                $output = $prediction['output'];
                // Output is usually an array of URLs
                $imageUrl = is_array($output) ? $output[0] : $output;
                return response()->json(['imageUrl' => $imageUrl]);
            } else if ($prediction['status'] === 'starting' || $prediction['status'] === 'processing') {
                // If it didn't finish in the wait time, we might need to handle polling on frontend
                // But for now, let's return the prediction status and let frontend handle it or just error.
                // Simplified: Return error saying "taking too long" or return the prediction ID.
                return response()->json(['error' => 'Generation taking too long', 'prediction' => $prediction], 202);
            } else {
                return response()->json(['error' => 'Generation failed', 'status' => $prediction['status']], 500);
            }

        } catch (\Exception $e) {
            Log::error('Image Generation Exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
