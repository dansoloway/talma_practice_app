<?php

namespace App\Services\Import;

use App\Models\Vocabulary;
use App\Services\ImageGeneration\ImageGeneratorService;
use App\Services\Translation\OpenAiTranslator;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Support\Facades\Log;

class VocabularyEnricher
{
    public function __construct(
        private OpenAiTranslator $translator,
        private ImageGeneratorService $imageGenerator,
        private ElevenLabsTtsService $ttsService,
    ) {}

    /**
     * @return array{translations_ok: bool, images_ok: bool, tts_ok: bool, errors: list<string>}
     */
    public function enrich(Vocabulary $vocabulary, SummerImportOptions $options): array
    {
        $result = [
            'translations_ok' => false,
            'images_ok' => false,
            'tts_ok' => false,
            'errors' => [],
        ];

        if ($options->translate && $this->translator->enabled()) {
            try {
                $needsHebrew = empty($vocabulary->hebrew_translation);
                $needsArabic = empty($vocabulary->arabic_translation);
                if ($needsHebrew || $needsArabic) {
                    $translations = $this->translator->translate(
                        $vocabulary->english_word,
                        $needsHebrew,
                        $needsArabic
                    );
                    $updates = [];
                    if ($needsHebrew && !empty($translations['hebrew'])) {
                        $updates['hebrew_translation'] = $translations['hebrew'];
                    }
                    if ($needsArabic && !empty($translations['arabic'])) {
                        $updates['arabic_translation'] = $translations['arabic'];
                    }
                    if ($updates !== []) {
                        $vocabulary->update($updates);
                    }
                }
                $result['translations_ok'] = true;
            } catch (\Throwable $e) {
                $result['errors'][] = "Translation failed for '{$vocabulary->english_word}': {$e->getMessage()}";
                Log::warning($result['errors'][array_key_last($result['errors'])]);
            }
        } elseif (!$options->translate) {
            $result['translations_ok'] = true;
        }

        if ($options->generateImages && $this->imageGenerator->enabled() && empty($vocabulary->image_path)) {
            try {
                $imagePath = $this->imageGenerator->generateVocabularyImage($vocabulary->english_word);
                if ($imagePath) {
                    $vocabulary->update(['image_path' => $imagePath]);
                    $result['images_ok'] = true;
                } else {
                    $result['errors'][] = "Image generation returned null for '{$vocabulary->english_word}'";
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "Image generation failed for '{$vocabulary->english_word}': {$e->getMessage()}";
                Log::warning($result['errors'][array_key_last($result['errors'])]);
            }
        } elseif (!$options->generateImages || !empty($vocabulary->image_path)) {
            $result['images_ok'] = !empty($vocabulary->image_path) || !$options->generateImages;
        }

        if ($options->generateTts && $this->ttsService->enabled() && !$vocabulary->hasAudioFile()) {
            try {
                $resultPath = $this->ttsService->generateAndSaveVocabulary(
                    $vocabulary->english_word,
                    null
                );
                if ($resultPath !== null) {
                    $vocabulary->update(['word_audio_path' => $resultPath['path']]);
                    $result['tts_ok'] = true;
                } else {
                    $result['errors'][] = "TTS generation returned null for '{$vocabulary->english_word}'";
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "TTS generation failed for '{$vocabulary->english_word}': {$e->getMessage()}";
                Log::warning($result['errors'][array_key_last($result['errors'])]);
            }
        } elseif (!$options->generateTts || $vocabulary->hasAudioFile()) {
            $result['tts_ok'] = $vocabulary->hasAudioFile() || !$options->generateTts;
        }

        return $result;
    }
}
