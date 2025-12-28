# TTS Regeneration Settings

## Overview
This document describes the configurable settings for TTS (Text-to-Speech) regeneration, including voice parameters, model settings, and processing options.

## Current Settings

### Voice Settings (ElevenLabs API)

Located in: `app/Services/Tts/ElevenLabsTtsService.php`

#### Vocabulary Preset (for single words)
```php
'vocabulary' => [
    'stability' => 0.8,           // Range: 0.0-1.0 (Higher = more consistent)
    'similarity_boost' => 0.85,   // Range: 0.0-1.0 (Higher = closer to original voice)
    'style' => 0.0,               // Range: 0.0-1.0 (Higher = more expressive)
    'use_speaker_boost' => true,  // Enhanced clarity
    'speed' => 1.0,               // Range: 0.25-4.0 (1.0 = normal speed)
]
```

#### Sentence Preset (for full sentences)
```php
'sentence' => [
    'stability' => 0.75,          // Slightly lower for more natural flow
    'similarity_boost' => 0.8,    // Clear voice characteristics
    'style' => 0.1,              // Slightly expressive
    'use_speaker_boost' => true,  // Enhanced clarity
    'speed' => 1.0,               // Default speed
]
```

### Voice ID
- **Default Voice**: `pNInz6obpgDQGcFmaJgB` (can be overridden via `ELEVENLABS_DEFAULT_VOICE_ID`)
- **Rachel Voice** (used for prompts/options): `EXAVITQu4vr4xnSDxMaL`

### Model
- **Current**: `eleven_multilingual_v2`
- **Alternative**: `eleven_monolingual_v1` (faster, English only)

## Configurable Parameters

### Environment Variables (.env)

Add these to your `.env` file to customize TTS generation:

```env
# Voice Settings
ELEVENLABS_DEFAULT_VOICE_ID=pNInz6obpgDQGcFmaJgB

# Vocabulary Preset (single words)
ELEVENLABS_VOCAB_STABILITY=0.8
ELEVENLABS_VOCAB_SIMILARITY=0.85
ELEVENLABS_VOCAB_STYLE=0.0
ELEVENLABS_VOCAB_SPEED=1.0
ELEVENLABS_VOCAB_SPEAKER_BOOST=true

# Sentence Preset (full sentences)
ELEVENLABS_SENTENCE_STABILITY=0.75
ELEVENLABS_SENTENCE_SIMILARITY=0.8
ELEVENLABS_SENTENCE_STYLE=0.1
ELEVENLABS_SENTENCE_SPEED=1.0
ELEVENLABS_SENTENCE_SPEAKER_BOOST=true

# Model Selection
ELEVENLABS_MODEL=eleven_multilingual_v2
# Options: eleven_multilingual_v2, eleven_monolingual_v1, eleven_turbo_v2_5

# Processing Settings
ELEVENLABS_TIMEOUT=30  # API timeout in seconds
```

## Parameter Explanations

### Stability (0.0 - 1.0)
- **Low (0.0-0.5)**: More variation, natural-sounding, but less consistent
- **Medium (0.5-0.7)**: Balanced between natural and consistent
- **High (0.7-1.0)**: Very consistent pronunciation, but may sound robotic

**Recommendation for vocabulary**: 0.7-0.9 (high consistency for learning)

### Similarity Boost (0.0 - 1.0)
- **Low (0.0-0.6)**: Voice may sound different from original
- **Medium (0.6-0.8)**: Good balance
- **High (0.8-1.0)**: Very close to original voice characteristics

**Recommendation**: 0.75-0.9 (clear voice for educational content)

### Style (0.0 - 1.0)
- **Low (0.0-0.3)**: Neutral, clear, professional
- **Medium (0.3-0.6)**: Slightly expressive
- **High (0.6-1.0)**: Very expressive, dramatic

**Recommendation for vocabulary**: 0.0-0.2 (clear and neutral)
**Recommendation for sentences**: 0.1-0.3 (slightly more natural)

### Speed (0.25 - 4.0)
- **0.5**: Half speed (very slow)
- **0.75**: Slow
- **1.0**: Normal speed
- **1.25**: Slightly fast
- **1.5**: Fast
- **2.0+**: Very fast

**Recommendation**: 0.9-1.1 (slightly slower for learning)

### Speaker Boost
- **true**: Enhanced clarity and intelligibility
- **false**: More natural, less processed

**Recommendation**: `true` for educational content

## Processing Settings

### Batch Processing
- **Items per request**: 1 (processes one word at a time)
- **Delay between requests**: 1 second (configurable in JavaScript)
- **Lock duration**: 5 seconds (prevents overlapping requests)

### Rate Limiting
- **API Timeout**: 30 seconds (configurable)
- **Delay between words**: 1 second (in JavaScript polling)

## Available Voices

Common ElevenLabs voice IDs:
- `pNInz6obpgDQGcFmaJgB` - Adam (default)
- `EXAVITQu4vr4xnSDxMaL` - Rachel (used for prompts)
- `21m00Tcm4TlvDq8ikWAM` - Rachel (alternative)
- `AZnzlk1XvdvUeBnXmlld` - Domi
- `ErXwobaYiN019PkySvjV` - Antoni
- `MF3mGyEYCl7XYWbV9V6O` - Elli
- `TxGEqnHWrfWFTfGW9XjX` - Josh
- `VR6AewLTigWG4xSOukaG` - Arnold
- `pNInz6obpgDQGcFmaJgB` - Adam
- `yoZ06aMxZJJ28mfd3POQ` - Sam

## Testing Different Settings

1. **Adjust in code**: Edit `app/Services/Tts/ElevenLabsTtsService.php` PRESETS array
2. **Test with single word**: Use the single word regeneration feature
3. **Compare results**: Listen to audio quality and consistency
4. **Adjust as needed**: Fine-tune based on your needs

## Recommendations by Use Case

### For Vocabulary Learning (Single Words)
```php
'stability' => 0.85,        // High consistency
'similarity_boost' => 0.9,  // Clear voice
'style' => 0.0,            // Neutral
'speed' => 0.95,           // Slightly slower
'use_speaker_boost' => true
```

### For Natural Sentences
```php
'stability' => 0.7,         // More natural variation
'similarity_boost' => 0.8,  // Good voice match
'style' => 0.2,            // Slightly expressive
'speed' => 1.0,            // Normal speed
'use_speaker_boost' => true
```

### For Fast Processing (Lower Quality)
```php
'stability' => 0.6,         // Lower = faster
'similarity_boost' => 0.7,  // Lower = faster
'style' => 0.0,            // Lower = faster
'speed' => 1.1,            // Slightly faster
'use_speaker_boost' => false // Disable for speed
```

## Notes

- Changes to presets require code modification (not yet configurable via .env)
- Model `eleven_multilingual_v2` supports multiple languages
- Model `eleven_monolingual_v1` is faster but English-only
- Higher stability = more API processing time
- Speaker boost adds slight processing overhead

