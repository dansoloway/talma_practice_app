# TTS Optimization Prompt for ChatGPT

## Context

I'm building an educational web application for early English literacy learners (young students). I need to optimize Text-to-Speech (TTS) settings specifically for **single English words** (vocabulary words like "cat", "dog", "students", "environment").

**Goal**: Achieve **consistent, clear pronunciation** that is:
- Easy for young learners to understand
- Consistent across multiple generations of the same word
- Clear and enunciated (not mumbled or rushed)
- Professional and educational (not overly expressive or dramatic)

## Current Implementation

I'm using **ElevenLabs API** with the following available parameters:

### Available Parameters

#### Voice Settings (all ranges are 0.0 to 1.0, except speed)

1. **stability** (0.0 - 1.0)
   - **Current default**: 0.8
   - **What it does**: Controls consistency vs variation in pronunciation
   - **Lower (0.0-0.5)**: More variation, natural-sounding, but less consistent
   - **Higher (0.7-1.0)**: Very consistent pronunciation, but may sound robotic
   - **Question**: For single words, should I prioritize consistency (higher) or naturalness (lower)?

2. **similarity_boost** (0.0 - 1.0)
   - **Current default**: 0.85
   - **What it does**: How closely the output matches the original voice characteristics
   - **Lower (0.0-0.6)**: Voice may sound different from original
   - **Higher (0.8-1.0)**: Very close to original voice characteristics
   - **Question**: What value provides the clearest, most intelligible pronunciation for learning?

3. **style** (0.0 - 1.0)
   - **Current default**: 0.0
   - **What it does**: Exaggeration of speaking style/expressiveness
   - **Lower (0.0-0.3)**: Neutral, clear, professional
   - **Higher (0.7-1.0)**: Very expressive, dramatic
   - **Question**: Should I keep this at 0.0 for clear, neutral pronunciation, or slightly higher for more natural delivery?

4. **speed** (0.25 - 4.0, but my code clamps to 0.7 - 1.2)
   - **Current default**: 1.0 (normal speed)
   - **What it does**: Speech rate multiplier
   - **Lower (0.7-0.9)**: Slower, clearer for learning
   - **Normal**: 1.0
   - **Higher (1.1-1.2)**: Faster
   - **Question**: What speed is optimal for young learners to clearly hear and process single words?

5. **use_speaker_boost** (boolean)
   - **Current default**: true
   - **What it does**: Enhances similarity to original voice and improves clarity
   - **Question**: Should I keep this enabled for maximum clarity?

#### Model Selection

Available Text-to-Speech models (note: `eleven_english_sts_v2` is Speech-to-Speech, not TTS):
- **eleven_monolingual_v1** (Current default)
  - English only
  - Faster generation
  - Good quality
  
- **eleven_multilingual_v2** (Currently set as fallback)
  - Supports 29 languages
  - More natural and consistent
  - Better quality overall
  - Same pricing

- **eleven_flash_v2**
  - English only
  - Ultra-fast (~75ms latency)
  - Lower cost (50% cheaper)
  - Good for batch processing

- **eleven_turbo_v2_5**
  - Ultra-low latency (~250-300ms)
  - High quality with low latency
  - Supports 32 languages
  - Good for real-time applications

- **eleven_flash_v2_5**
  - Fastest generation (~75ms latency)
  - Supports 32 languages
  - Lower cost (50% cheaper)
  - Good for batch processing
  - Slightly lower quality than v2

- **eleven_v3** (Alpha)
  - Most emotionally expressive
  - 70+ languages
  - Higher character limits
  - May be overkill for single words

**Question**: Which model provides the best balance of quality and consistency for single English words? Should I use `eleven_flash_v2` (English-only, fast, cheaper) or `eleven_multilingual_v2` (better quality)?

#### Voice ID

- **Current default**: `pNInz6obpgDQGcFmaJgB` (Adam voice)
- Can be overridden with any ElevenLabs voice ID
- **Question**: Are there specific voices that are better for educational content or clearer pronunciation?

## Current Settings Summary

```php
'vocabulary' => [
    'stability' => 0.8,
    'similarity_boost' => 0.85,
    'style' => 0.0,
    'use_speaker_boost' => true,
    'speed' => 1.0,
],
'model_id' => 'eleven_monolingual_v1',
```

## Specific Questions

1. **What are the optimal parameter values for single English words** to achieve:
   - Maximum clarity and intelligibility
   - Consistency across multiple generations
   - Appropriate pace for young learners
   - Professional, educational tone

2. **Should I use different settings for:**
   - Short words (1-3 syllables: "cat", "dog", "run")
   - Medium words (4-6 syllables: "students", "environment")
   - Long words (7+ syllables: "responsibility")

3. **Model recommendation**: Should I stick with `eleven_monolingual_v1` or upgrade to `eleven_multilingual_v2` for better quality/consistency?

4. **Speed optimization**: Is 1.0 optimal, or should I slow it down slightly (0.9-0.95) for better clarity?

5. **Stability vs Naturalness trade-off**: For educational content, should I prioritize consistency (higher stability) even if it sounds slightly less natural?

6. **Any other recommendations** for improving pronunciation clarity and consistency?

## Example Words to Consider

- Simple: "cat", "dog", "run", "jump"
- Medium: "students", "teacher", "classroom", "library"
- Complex: "environment", "responsibility", "mathematics", "experiment"
- Multi-syllable: "beautiful", "important", "different", "together"

## Constraints

- Must work well for **single words only** (not sentences)
- Target audience: **Young English learners** (elementary school age)
- Need **consistency** - same word should sound the same every time
- Need **clarity** - every syllable should be clearly pronounced
- Educational context - professional, clear, not overly dramatic

## Expected Output

Please provide:
1. **Recommended parameter values** for single English words
2. **Model recommendation** with reasoning
3. **Speed recommendation** with reasoning
4. **Any additional tips** for optimizing pronunciation clarity
5. **Trade-offs** to be aware of (e.g., consistency vs naturalness)

---

**Copy this entire prompt and paste it into ChatGPT to get optimized TTS settings recommendations.**
