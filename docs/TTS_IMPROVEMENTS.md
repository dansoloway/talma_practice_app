# TTS Improvements - ElevenLabs API Options

## Current Implementation

Your app currently uses ElevenLabs API with these settings:

### Current Settings (varies by location):
- **Model**: `eleven_monolingual_v1` (older model)
- **Voice ID**: 
  - `pNInz6obpgDQGcFmaJgB` (VocabularyController - default voice)
  - `EXAVITQu4vr4xnSDxMaL` (Rachel voice - used in some places)
- **Voice Settings**:
  - Most places: `stability: 0.5`, `similarity_boost: 0.75`
  - VocabularyController: `stability: 0.65`, `similarity_boost: 0.6`, `style: 0.2`, `speed: 0.85`, `use_speaker_boost: false`

## Available ElevenLabs API Options

### 1. **Model Selection** (Upgrade Available)

#### Current: `eleven_monolingual_v1`
- Older model, English only
- Good quality but limited

#### Better Options:
- **`eleven_multilingual_v2`** ⭐ RECOMMENDED
  - Supports 29 languages (Hebrew, Arabic, English, etc.)
  - More natural and consistent
  - Better for multilingual learning apps
  - Same pricing as monolingual

- **`eleven_turbo_v2_5`** (Fast, lower latency)
  - Ultra-low latency (~75ms)
  - Good for real-time applications
  - Slightly lower quality than v2

- **`eleven_flash_v2_5`** (Fastest)
  - Fastest generation
  - Good for batch processing
  - Lower quality than v2

### 2. **Voice Settings Parameters**

All parameters are 0.0 to 1.0 (except speed):

#### **stability** (0.0 - 1.0)
- **Current**: 0.5-0.65
- **What it does**: Controls consistency vs variation
- **Lower (0.0-0.3)**: More variation, expressive, less consistent
- **Higher (0.7-1.0)**: More consistent, stable, less variation
- **Recommendation**: 
  - For vocabulary words: 0.7-0.8 (consistent pronunciation)
  - For sentences: 0.5-0.6 (more natural)

#### **similarity_boost** (0.0 - 1.0)
- **Current**: 0.6-0.75
- **What it does**: How closely to match the original voice
- **Lower (0.0-0.5)**: More variation from original voice
- **Higher (0.7-1.0)**: Closer to original voice characteristics
- **Recommendation**: 0.75-0.85 for learning apps (clear, consistent)

#### **style** (0.0 - 1.0) ⭐ NEW OPTION
- **Current**: Only used in VocabularyController (0.2)
- **What it does**: Exaggeration of speaking style
- **Lower (0.0-0.3)**: Neutral, professional
- **Higher (0.7-1.0)**: More expressive, dramatic
- **Recommendation**: 0.0-0.2 for educational content (clear, neutral)

#### **use_speaker_boost** (boolean) ⭐ NEW OPTION
- **Current**: false (only in VocabularyController)
- **What it does**: Enhances similarity to original voice
- **Recommendation**: `true` for better voice clarity

#### **speed** (0.25 - 4.0) ⭐ NEW OPTION
- **Current**: 0.85 (only in VocabularyController)
- **What it does**: Speech rate multiplier
- **Lower (0.5-0.8)**: Slower, clearer for learning
- **Normal**: 1.0
- **Higher (1.2-1.5)**: Faster
- **Recommendation**: 
  - Vocabulary words: 0.85-0.9 (slightly slower for clarity)
  - Sentences: 1.0 (normal speed)

### 3. **Voice Selection**

#### Current Voices:
- `pNInz6obpgDQGcFmaJgB` - Default voice
- `EXAVITQu4vr4xnSDxMaL` - Rachel voice

#### Options:
- **Browse voices**: https://elevenlabs.io/app/voices
- **Use different voices** for different content types
- **Voice cloning**: Create custom voices (requires samples)
- **Voice design**: Generate synthetic voices

### 4. **Output Format Options**

Currently using default (MP3). Available:
- **MP3** (current) - Good compression, universal support
- **PCM** - Uncompressed, highest quality
- **Opus** - Good compression, web-friendly

### 5. **Optimization Settings**

#### **Optimize Streaming Latency** (for real-time)
- Can reduce latency for better user experience
- Trade-off: slightly lower quality

#### **Output Sample Rate**
- Default: 22050 Hz (good quality)
- Higher: 44100 Hz (better quality, larger files)
- Lower: 11025 Hz (smaller files, lower quality)

## Recommended Improvements

### Priority 1: Upgrade Model
```php
'model_id' => 'eleven_multilingual_v2', // Instead of 'eleven_monolingual_v1'
```
**Benefits**: Better quality, multilingual support, more natural

### Priority 2: Optimize Voice Settings

#### For Vocabulary Words (single words):
```php
'voice_settings' => [
    'stability' => 0.75,           // More consistent pronunciation
    'similarity_boost' => 0.8,     // Clear voice characteristics
    'style' => 0.0,                // Neutral, clear
    'use_speaker_boost' => true,   // Enhanced clarity
    'speed' => 0.9,                // Slightly slower for clarity
]
```

#### For Sentences:
```php
'voice_settings' => [
    'stability' => 0.6,            // More natural variation
    'similarity_boost' => 0.75,    // Good voice match
    'style' => 0.1,               // Slightly expressive
    'use_speaker_boost' => true,   // Enhanced clarity
    'speed' => 1.0,               // Normal speed
]
```

### Priority 3: Consistent Configuration

Create a centralized TTS service class to:
- Standardize settings across all TTS generation
- Make it easy to adjust settings
- Support different presets (vocabulary vs sentences)

### Priority 4: Voice Selection

Consider:
- Different voices for different languages (Hebrew voice, Arabic voice)
- Gender options (male/female voices)
- Age-appropriate voices for learning

## Implementation Locations

TTS generation happens in:
1. `app/Http/Controllers/Admin/VocabularyController.php` - Vocabulary words
2. `app/Http/Controllers/Admin/PromptController.php` - Option words and sentences
3. `app/Http/Controllers/Admin/GeneratesTtsAudio.php` - Word and sentence TTS
4. `app/Console/Commands/GenerateVocabularyTts.php` - CLI command
5. `app/Console/Commands/GenerateVocabularyAudio.php` - CLI command
6. `app/Console/Commands/BuildWordAudio.php` - Batch word audio
7. `scripts/generate_audio.php` - Standalone script

## Prompting Cues (Emotional/Tonal Context)

You can now use prompting cues to influence how the text is spoken! The service automatically prepends cues to the text.

### Available Cues:
- `calmly` - Calm, relaxed speech
- `clearly` - Clear, enunciated pronunciation
- `slowly` - Slower pace
- `excitedly` - Energetic, excited tone
- `softly` - Gentle, quiet speech
- `confidently` - Confident, assertive tone
- `patiently` - Patient, measured pace
- `warmly` - Warm, friendly tone

### Usage Examples:

```php
// In VocabularyController or GeneratesTtsAudio trait:

// Vocabulary words - clear and calm
$audioData = $ttsService->generateVocabulary(
    $word,
    null, // Use default voice
    ['calmly', 'clearly'] // Multiple cues
);

// Sentences - natural and calm
$audioData = $ttsService->generateSentence(
    $sentence,
    null,
    ['calmly'] // Single cue
);

// Or use the general generate method
$audioData = $ttsService->generate(
    $text,
    'vocabulary', // or 'sentence'
    null,
    ['clearly', 'slowly'] // Your custom cues
);
```

### How It Works:
The service prepends cues to your text. For example:
- Text: `"students"`
- Cues: `['calmly', 'clearly']`
- Sent to API: `"calmly and clearly, students"`

The ElevenLabs API interprets these cues and adjusts the speech accordingly!

## Implementation Status

✅ **Completed:**
- Created `ElevenLabsTtsService` with high stability settings
- Upgraded to `eleven_multilingual_v2` model
- Updated `VocabularyController` to use new service
- Updated `GeneratesTtsAudio` trait to use new service
- Added prompting cue support

🔄 **Remaining:**
- Update `PromptController` methods
- Update CLI commands (`GenerateVocabularyTts`, `GenerateVocabularyAudio`, `BuildWordAudio`)
- Update `scripts/generate_audio.php`

## Next Steps

1. ✅ **Service created** - `App\Services\Tts\ElevenLabsTtsService`
2. ✅ **High stability settings** - Configured for clarity and consistency
3. ✅ **Prompting cues** - Support for emotional/tonal context
4. 🔄 **Update remaining locations** - CLI commands and scripts
5. **Test**: Generate a few words/sentences to verify quality

## API Documentation

Full ElevenLabs API docs: https://elevenlabs.io/docs/api-reference/text-to-speech

