<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Story;
use App\Services\DeepLService;
use App\Services\PollyService;

class TranslationController extends Controller
{
    private DeepLService $deepl;
    private PollyService $polly;

    public function __construct(DeepLService $deepl, PollyService $polly)
    {
        $this->deepl = $deepl;
        $this->polly = $polly;
    }

    public function translateStory(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'target_lang' => 'required|string|size:2',
            'with_audio' => 'boolean',
            'engine' => 'string',
            'voice_id' => 'string'
        ]);

        $targetLang = $validated['target_lang'];
        $withAudio = $validated['with_audio'] ?? false;
        $engine = $validated['engine'] ?? 'standard';
        $voiceId = $validated['voice_id'] ?? null;
        
        $pollyLangCode = $this->getPollyLangCode($targetLang);
        \Illuminate\Support\Facades\Log::info("Translation request for lang: {$targetLang}, mapped to Polly code: {$pollyLangCode}");
        
        if (!$voiceId && $withAudio) {
            $voicesResult = $this->polly->getVoices($pollyLangCode);
            \Illuminate\Support\Facades\Log::info("Polly getVoices result:", $voicesResult);
            
            if ($voicesResult['success'] && !empty($voicesResult['voices'])) {
                // Let's find a voice that supports 'neural' or 'standard'
                $selectedVoice = $voicesResult['voices'][0];
                foreach ($voicesResult['voices'] as $voice) {
                    if (in_array('neural', $voice['engines'])) {
                        $selectedVoice = $voice;
                        break;
                    }
                }
                
                $voiceId = $selectedVoice['id'];
                if (isset($selectedVoice['engines']) && !empty($selectedVoice['engines'])) {
                    if (!in_array($engine, $selectedVoice['engines'])) {
                         // Fallback to the best available engine (prefer neural)
                         $engine = in_array('neural', $selectedVoice['engines']) ? 'neural' : $selectedVoice['engines'][0];
                    }
                }
            } else {
                 return response()->json(['error' => 'No voices available for this language', 'pollyResult' => $voicesResult], 400);
            }
        }

        /** @var Story $story */
        // Assuming both parents and admins can translate stories they have access to. Wait, is it their own story? 
        $story = Story::findOrFail($id); 
        
        // You might want to add authorization checks here.

        $output = $story->output;
        
        if (!is_array($output) || !isset($output['slides'])) {
             return response()->json(['error' => 'Invalid story format'], 400);
        }

        // Initialize translations array if it doesn't exist
        if (!isset($output['translations'])) {
            $output['translations'] = [];
        }
        
        $translatedData = [
            'title' => '',
            'description' => '',
            'slides' => []
        ];

        // 1. Translate Title & Description
        $originalTitle = $output['title'] ?? $story->story_subject;
        if ($originalTitle) {
            $titleTrans = $this->deepl->translate($originalTitle, $targetLang);
            if ($titleTrans['success']) {
                $translatedData['title'] = $titleTrans['text'];
            } else {
                return response()->json(['error' => 'Translation failed', 'details' => $titleTrans], 500);
            }
        }

        $originalDesc = $output['formData']['description'] ?? '';
        if ($originalDesc) {
             $descTrans = $this->deepl->translate($originalDesc, $targetLang);
             if ($descTrans['success']) {
                 $translatedData['description'] = $descTrans['text'];
             }
        }

        // 2. Iterate through Slides and freeElements within to find text
        foreach ($output['slides'] as $slideIndex => $slide) {
            $translatedSlide = [
                'id' => $slide['id'],
                'audioUrl' => null, // We'll set this if TTS is enabled
                'content' => '', // Generic content if it's there
                'freeElements' => []
            ];

            // If layout uses generic content text (some layouts might use this)
            // But usually freeElements are used. Just in case, translating.
            // Actually 'content' is often an array or unused in new versions. Let's focus on freeElements.

            $slideFullText = ''; // Collect all text in a slide to generate a single TTS audio file.

            if (isset($slide['freeElements']) && is_array($slide['freeElements'])) {
                foreach ($slide['freeElements'] as $idx => $element) {
                    $clonedElement = $element;
                    if ($element['type'] === 'text' && !empty($element['content'])) {
                        $transResult = $this->deepl->translate($element['content'], $targetLang);
                        if ($transResult['success']) {
                            $clonedElement['content'] = $transResult['text'];
                            $slideFullText .= $transResult['text'] . " ";
                        }
                    }
                    $translatedSlide['freeElements'][] = $clonedElement;
                }
            }
            
            // Generate Audio for the slide if requested
            if ($withAudio && !empty(trim($slideFullText))) {
                $ttsResult = $this->polly->synthesize(trim($slideFullText), $voiceId, $engine, $pollyLangCode);
                if ($ttsResult['success']) {
                    $translatedSlide['audioUrl'] = $ttsResult['audio_url'];
                }
            }

            $translatedData['slides'][] = $translatedSlide;
        }

        // 3. Save to output->translations
        $output['translations'][$targetLang] = $translatedData;
        $story->output = $output;
        $story->save();

        return response()->json([
            'success' => true,
            'message' => 'Story translated successfully',
            'story' => $story
        ]);
    }

    private function getPollyLangCode(string $targetLang): string
    {
        $map = [
            'en' => 'en-US',
            'it' => 'it-IT',
            'de' => 'de-DE',
            'fr' => 'fr-FR',
            'es' => 'es-ES',
            'pt' => 'pt-PT',
            'nl' => 'nl-NL',
            'pl' => 'pl-PL',
            'ru' => 'ru-RU',
            'ja' => 'ja-JP',
            'zh' => 'cmn-CN',
            'ko' => 'ko-KR',
        ];
        
        return $map[$targetLang] ?? 'en-US';
    }
}
