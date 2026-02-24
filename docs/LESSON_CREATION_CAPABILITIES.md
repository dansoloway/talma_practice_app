# TALMA Practice Pal - Lesson Creation Capabilities

## Overview

This document describes how to create complete lessons from vocabulary lists, including automatic game generation, content enrichment, and bulk operations. The system is designed to take a simple vocabulary list and automatically create a full-featured lesson with games, audio, images, and translations.

## Core Workflow: From Vocabulary List to Complete Lesson

### Step 1: Import Vocabulary List

The system accepts vocabulary in multiple formats:

#### Option A: CSV Import (Recommended for Bulk Creation)

**CSV Format:**
```csv
English Word,Hebrew Translation,Arabic Translation
science,מדע,علم
experiment,ניסוי,تجربة
volcano,הר געש,بركان
variable,משתנה,متغير
```

**Features:**
- English word is required
- Hebrew and Arabic translations are optional
- If translations are blank, system automatically generates them via OpenAI
- Can import hundreds of words at once
- Two import modes:
  - **Add**: Adds to existing vocabulary
  - **Replace**: Replaces all vocabulary in the lesson

**Process:**
1. Upload CSV file via admin panel
2. System validates format
3. For each word:
   - Creates vocabulary record
   - Auto-generates Hebrew/Arabic translation (if missing)
   - Auto-generates audio pronunciation (TTS)
   - Optionally auto-generates image (if enabled)
4. After import completes, automatically creates games

#### Option B: Manual Entry

- Add words one-by-one via admin interface
- Each word automatically gets:
  - Audio pronunciation
  - Translation (if OpenAI configured)
  - Image (if auto-generation enabled)

#### Option C: Bulk Lesson Import

**Two CSV Files:**

**Sessions CSV** (`we speak vocab - sessions.csv`):
```csv
id,grade_level,session_title,title
1,7th Grade,Session 3,Making a Volcano
2,7th Grade,Session 4 - Part A,The Scientific Method
```

**Vocabulary CSV** (`we speak vocab - vocab.csv`):
```csv
session_id,word,hebrew,arabic
1,science,,
1,experiment,,
1,volcano,,
2,ice,,
2,melts,,
```

**Command:**
```bash
php artisan talma:import-lessons
```

**What It Does:**
- Creates lessons from sessions CSV
- Links vocabulary to lessons
- Auto-translates missing translations
- Auto-generates audio for all words
- Creates games automatically for each lesson

## Automatic Content Generation

### 1. Translation Generation

**How It Works:**
- Uses OpenAI GPT-4o-mini (or GPT-4o as fallback)
- Translates English → Hebrew and English → Arabic
- Only translates if translation is missing
- Caches translations (doesn't re-translate existing words)

**When It Happens:**
- During CSV import
- When adding vocabulary manually
- Can be triggered manually for bulk operations

**API Required:** `OPENAI_API_KEY` in `.env`

### 2. Audio Generation (TTS)

**How It Works:**
- Uses ElevenLabs API (pre-generation, not runtime)
- Generates MP3 audio files for each vocabulary word
- Stores audio in `storage/app/public/tts/`
- Audio is pre-generated (instant playback for students)

**When It Happens:**
- Automatically when vocabulary is created
- Can be regenerated in bulk
- Uses consistent voice (Rachel voice ID: EXAVITQu4vr4xnSDxMaL)

**API Required:** `ELEVENLABS_API_KEY` in `.env`

**Bulk Generation:**
```bash
# Generate audio for all vocabulary in a lesson
POST /admin/lessons/{lesson}/vocabulary/generate-tts
```

### 3. Image Generation

**How It Works:**
- Multiple image services available (priority order):
  1. **Flaticon** - Icon/clipart search (preferred for flashcards)
  2. **Unsplash** - Stock photos
  3. **Pixabay** - Stock photos
  4. **Leonardo.ai** - AI-generated images
  5. **OpenAI DALL-E** - AI-generated images

**When It Happens:**
- Can be enabled during CSV import
- Can be triggered manually for individual words
- Can be bulk-generated for all vocabulary
- Auto-image finder tool lets you search and select images

**Bulk Image Generation:**
```bash
# Generate images for all vocabulary in a lesson
POST /admin/lessons/{lesson}/vocabulary/generate-images
```

**Auto-Image Finder:**
- Search for images by word
- Preview multiple options
- Select and apply image
- System downloads and stores automatically

**APIs Required:** 
- `FLATICON_API_KEY` (preferred)
- `UNSPLASH_ACCESS_KEY` (optional fallback)
- `PIXABAY_API_KEY` (optional fallback)
- `LEONARDO_API_KEY` (optional)
- `OPENAI_API_KEY` (optional, for DALL-E)

## Automatic Game Generation

### Games Created Automatically

When vocabulary is imported (via CSV or bulk import), the system automatically creates:

#### 1. Matching Game
- **Created:** Automatically if lesson has 2+ vocabulary words
- **Uses:** All vocabulary from the lesson
- **Game Types:** 
  - Image ↔ Image
  - Image ↔ Word
  - Word ↔ Word
  - Audio ↔ Word
  - Audio ↔ Image
- **Grid Size:** Automatically determined based on vocabulary count
- **Limitation:** Maximum 30 words for matching games (larger sets split)

#### 2. Flashcard Game
- **Created:** Automatically if lesson has 1+ vocabulary words
- **Uses:** All vocabulary from the lesson
- **Game Types:** Automatically determined based on available assets:
  - If images exist: `image_to_word`
  - If audio exists: `audio_to_word`
  - If both exist: Both types enabled
- **Cards Per Game:** Default 10 (configurable, max 50)
- **Language Support:** English, Hebrew, Arabic options

#### 3. Spelling Game
- **Created:** Automatically if lesson has vocabulary with audio
- **Uses:** All vocabulary from the lesson
- **Features:**
  - Audio pronunciation plays
  - Student types the word
  - Immediate feedback

### Manual Game Creation

Games can also be created manually with custom configurations:

- **Matching Games:** Custom grid size, game type, vocabulary selection
- **Flashcard Games:** Custom game types, card count, vocabulary selection
- **Spelling Games:** Custom vocabulary selection
- **Sentence Builder Games:** Custom sentence sets
- **True/False Games:** Can be auto-generated from vocabulary (see below)

### True/False Game Auto-Generation

**How It Works:**
- Uses OpenAI to generate questions from lesson vocabulary
- Generates 5-8 questions per game
- Supports difficulty levels: `easy`, `medium`, `hard`
- Questions are generated as drafts (require admin approval)

**Process:**
1. Create True/False game
2. Click "Generate Questions" button
3. System uses OpenAI to create questions based on vocabulary
4. Questions appear as drafts
5. Admin reviews and approves/rejects questions
6. Approved questions become active

**Example Generated Questions:**
- "A volcano is a type of mountain." (True)
- "Ice melts when heated." (True)
- "Science experiments always require fire." (False)

**API Required:** `OPENAI_API_KEY` in `.env`

### Sentence Builder Game Auto-Generation

**How It Works:**
- Uses OpenAI to generate sentence-building exercises
- Creates questions where students arrange words in correct order
- Based on lesson vocabulary and grammar concepts

**Process:**
1. Create Sentence Builder game
2. Click "Generate Questions" button
3. System generates sentence-building exercises
4. Admin can edit/delete generated questions

## Content Enrichment Workflow

### Complete Automated Workflow

**Input:** CSV file with vocabulary list

**Process:**
1. **Import Vocabulary**
   - Parse CSV
   - Create vocabulary records
   - Extract English words

2. **Auto-Translate** (if translations missing)
   - Call OpenAI API for each word
   - Generate Hebrew translation
   - Generate Arabic translation
   - Store translations

3. **Auto-Generate Audio** (for each word)
   - Call ElevenLabs API
   - Generate MP3 file
   - Store in `storage/app/public/tts/`
   - Link to vocabulary record

4. **Auto-Generate Images** (if enabled)
   - Search Flaticon/Unsplash/etc.
   - Download best match
   - Store in `storage/app/public/images/`
   - Link to vocabulary record

5. **Auto-Create Games**
   - Create Matching Game (if 2+ words)
   - Create Flashcard Game (if 1+ words)
   - Create Spelling Game (if audio exists)
   - Configure games with appropriate vocabulary

**Output:** Complete lesson ready for students with:
- ✅ Vocabulary with translations
- ✅ Audio pronunciations
- ✅ Images
- ✅ Multiple game types
- ✅ Ready to publish

### Manual Enrichment Options

If automatic generation isn't desired, you can:

1. **Manual Image Upload**
   - Upload images individually
   - Use auto-image finder to search and select
   - Bulk upload via admin interface

2. **Manual Translation Entry**
   - Enter translations manually
   - Import translations via CSV
   - Edit translations after auto-generation

3. **Manual Game Configuration**
   - Create games manually
   - Select specific vocabulary
   - Customize game settings

## Bulk Operations

### Bulk Vocabulary Operations

**Generate Audio for All Vocabulary:**
```
POST /admin/lessons/{lesson}/vocabulary/generate-tts
```
- Generates audio for all words without audio
- Processes in batch
- Shows progress

**Generate Images for All Vocabulary:**
```
POST /admin/lessons/{lesson}/vocabulary/generate-images
```
- Generates images for all words without images
- Uses configured image service priority
- Processes in batch

**Bulk Translation:**
- Happens automatically during import
- Can be triggered manually for existing vocabulary
- Only translates missing translations

### Bulk Game Creation

**Create Missing Games:**
```bash
php artisan games:create-missing
```
- Scans all lessons
- Creates missing games (matching, flashcard, spelling)
- Only creates if vocabulary exists
- Skips lessons that already have games

**Auto-Create Games for Lesson:**
- Happens automatically after vocabulary import
- Can be triggered manually via admin interface
- Creates all applicable game types

## CSV Import Capabilities

### Vocabulary CSV Import

**Location:** `/admin/lessons/{lesson}/vocabulary/csv/upload`

**CSV Format:**
```csv
English Word,Hebrew Translation,Arabic Translation
science,מדע,علم
experiment,ניסוי,تجربة
volcano,,
```

**Features:**
- Template download available
- Preview before import
- Validation with error messages
- Import modes: Add or Replace
- Auto-translation for missing translations
- Auto-audio generation
- Auto-image generation (if enabled)
- Auto-game creation after import

**Limitations:**
- Max file size: 2MB
- Must have header row
- English word required

### Prompt CSV Import

**Location:** `/admin/lessons/{lesson}/prompts/import`

**CSV Format:**
```csv
Prompt Text,Template,Option 1,Option 2,Option 3,Correct
Complete the sentence,The ice {} in the sun,m freezes,m melts,m boils,2
What happens?,Water {} when heated,m freezes,m evaporates,m condenses,2
```

**Features:**
- Creates prompts with options
- Auto-generates sentence audio (template + option)
- Auto-generates word audio for options
- Preview before import
- Validation

### Bulk Lesson Import

**Command:** `php artisan talma:import-lessons`

**Required Files:**
- `data/we speak vocab - sessions.csv` - Lesson metadata
- `data/we speak vocab - vocab.csv` - Vocabulary words

**Process:**
1. Parses sessions CSV → creates lessons
2. Parses vocabulary CSV → links to lessons
3. Auto-translates missing translations
4. Auto-generates audio
5. Auto-creates games

**Features:**
- Skips existing lessons (no duplicates)
- Shows import summary
- Error handling and reporting
- Can be run multiple times safely

## Advanced Features

### Lesson Combining

**Feature:** Combine multiple lessons into one

**Use Case:** Merge related lessons or create review lessons

**Process:**
1. Select source lessons
2. System combines vocabulary
3. Creates new lesson with combined content
4. Games automatically created from combined vocabulary

### Review Lessons

**Feature:** Create lessons that review vocabulary from multiple source lessons

**Use Case:** Cumulative review, end-of-unit practice

**Process:**
1. Create new lesson
2. Select source lessons
3. Optionally select specific vocabulary to review
4. System creates games from selected vocabulary

### Grammar Set Integration

**Feature:** Attach grammar concepts to lessons

**Use Case:** Grammar-focused practice

**Process:**
1. Create grammar sets (via CSV or manually)
2. Attach grammar sets to lessons
3. Grammar concepts available for activities

## Content Creation Best Practices

### Recommended Workflow

1. **Prepare Vocabulary List**
   - Organize words by lesson/topic
   - Include English words (required)
   - Include translations if available (optional)

2. **Create Lesson**
   - Via admin panel or CSV import
   - Set title, grade level, session info

3. **Import Vocabulary**
   - Upload CSV with vocabulary
   - System auto-translates, generates audio/images
   - System auto-creates games

4. **Review and Refine**
   - Check translations (edit if needed)
   - Review images (replace if needed)
   - Test audio playback
   - Test games

5. **Add Prompts** (Optional)
   - Create sentence completion prompts
   - Import via CSV or create manually
   - System auto-generates sentence audio

6. **Publish**
   - Mark lesson as active
   - Students can access immediately

### Tips for Large Vocabulary Lists

- **Batch Processing:** System handles large lists automatically
- **Progress Tracking:** Monitor import progress
- **Error Handling:** System reports errors without stopping
- **Incremental Import:** Can import in chunks if needed
- **Game Limits:** Matching games limited to 30 words (system handles splitting)

## Current Limitations

### What Works Automatically
- ✅ Vocabulary import (CSV)
- ✅ Translation generation (OpenAI)
- ✅ Audio generation (ElevenLabs)
- ✅ Image generation (multiple services)
- ✅ Game creation (matching, flashcard, spelling)
- ✅ True/False question generation (OpenAI)
- ✅ Sentence Builder question generation (OpenAI)

### What Requires Manual Work
- ⚠️ Prompt creation (sentence completion) - can import CSV but need to create templates
- ⚠️ Image selection - auto-finder helps but may need manual review
- ⚠️ Game customization - games auto-created but can be customized manually
- ⚠️ Activity ordering - need to arrange activities manually

### What's Not Automated
- ❌ Automatic prompt generation from vocabulary
- ❌ Automatic sentence template creation
- ❌ Automatic activity sequencing
- ❌ Automatic difficulty adjustment

## Integration Points for Custom Development

If you want to create lessons programmatically or integrate with external systems:

### API Endpoints (Admin)
- `POST /admin/lessons` - Create lesson
- `POST /admin/lessons/{lesson}/vocabulary` - Add vocabulary
- `POST /admin/lessons/{lesson}/vocabulary/bulk` - Bulk add vocabulary
- `POST /admin/lessons/{lesson}/vocabulary/generate-tts` - Generate audio
- `POST /admin/lessons/{lesson}/vocabulary/generate-images` - Generate images

### Artisan Commands
- `php artisan talma:import-lessons` - Import lessons from CSV
- `php artisan games:create-missing` - Create missing games
- `php artisan tts:build-assets` - Generate TTS assets

### Service Classes
- `App\Services\Translation\OpenAiTranslator` - Translation service
- `App\Services\Tts\ElevenLabsTtsService` - TTS service
- `App\Services\ImageGeneration\*` - Image generation services
- `App\Services\QuestionGeneration\OpenAiQuestionGenerator` - Question generation

---

## Summary

**Input:** Vocabulary list (CSV or manual entry)

**Automatic Processing:**
1. Translations (Hebrew/Arabic)
2. Audio pronunciations
3. Images (optional)
4. Games (matching, flashcard, spelling)

**Output:** Complete lesson ready for students

**Time to Create Lesson:** 
- Small vocabulary (10-20 words): ~2-5 minutes
- Medium vocabulary (50-100 words): ~10-15 minutes
- Large vocabulary (200+ words): ~30-60 minutes

The system is designed to minimize manual work and maximize automation, allowing teachers to focus on content quality rather than technical setup.
