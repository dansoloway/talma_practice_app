# CSV Import Code Reference

This document identifies the specific code files and methods that handle CSV vocabulary import and bulk lesson import.

## CSV Vocabulary Import

### Main Controller
**File:** `app/Http/Controllers/Admin/VocabularyController.php`

#### Key Methods:

1. **`csvUpload(Lesson $lesson)`** (Line 306)
   - Shows the CSV upload form
   - Route: `GET /admin/lessons/{lesson}/vocabulary/csv/upload`
   - View: `resources/views/admin/vocabulary/csv-upload.blade.php`

2. **`processCsv(Request $request, Lesson $lesson)`** (Line 314)
   - **Main import handler** - processes the uploaded CSV file
   - Route: `POST /admin/lessons/{lesson}/vocabulary/csv/process`
   - **What it does:**
     - Validates CSV file (max 2MB, CSV/TXT format)
     - Parses CSV data
     - Handles import mode: `add` or `replace`
     - For each row:
       - Validates English word (only English characters)
       - Checks for duplicates
       - Auto-translates (Hebrew/Arabic) if translator enabled
       - Creates vocabulary record
       - Auto-generates image (if image generator enabled)
       - Auto-generates TTS audio
     - Calls `createGamesForLesson()` after import
     - Returns success/error summary

3. **`csvTemplate()`** (Line 482)
   - Downloads CSV template file
   - Route: `GET /admin/lessons/vocabulary/csv/template`
   - Returns sample CSV with example vocabulary

4. **`createGamesForLesson(Lesson $lesson)`** (Line 1138) - **Private method**
   - Automatically creates games after vocabulary import
   - Creates Matching Game (if 2+ words)
   - Creates Flashcard Game (if 1+ words)
   - Creates Spelling Game (if vocabulary exists)
   - Called automatically after CSV import completes

### Routes
**File:** `routes/web.php`

```php
// CSV upload form
Route::get('lessons/{lesson}/vocabulary/csv/upload', [AdminVocabularyController::class, 'csvUpload'])
    ->name('lessons.vocabulary.csv.upload');

// Process CSV import
Route::post('lessons/{lesson}/vocabulary/csv/process', [AdminVocabularyController::class, 'processCsv'])
    ->name('lessons.vocabulary.csv.process');

// Download CSV template
Route::get('lessons/vocabulary/csv/template', [AdminVocabularyController::class, 'csvTemplate'])
    ->name('lessons.vocabulary.csv.template');
```

### CSV Format Expected

**Simple format (English words only):**
```csv
science
experiment
volcano
```

**With translations:**
```csv
English Word,Hebrew Translation,Arabic Translation
science,מדע,علم
experiment,ניסוי,تجربة
```

**Note:** The system accepts just the first column (English word). Translations are optional and will be auto-generated if missing.

### Dependencies Used

- **Translation Service:** `App\Services\Translation\OpenAiTranslator`
  - Called via `$this->translator->translate()` (Line 408)
  - Auto-translates missing Hebrew/Arabic translations

- **Image Generator:** `App\Services\ImageGeneration\ImageGeneratorService`
  - Called via `$this->imageGenerator->generateVocabularyImage()` (Line 431)
  - Auto-generates images if enabled

- **TTS Service:** `App\Services\Tts\ElevenLabsTtsService`
  - Called via `$this->generateVocabularyAudio()` (Line 442)
  - Auto-generates audio for each vocabulary word

---

## Bulk Lesson Import

### Main Command
**File:** `app/Console/Commands/ImportPracticePalLessons.php`

#### Key Method:

**`handle()`** (Line 30)
- **Main entry point** for bulk lesson import
- Command: `php artisan talma:import-lessons`
- Alias: `php artisan wespeak:import-lessons`
- **What it does:**
  - Checks if CSV files exist (`data/we speak vocab - sessions.csv` and `data/we speak vocab - vocab.csv`)
  - Checks for existing lessons (prompts user if found)
  - Calls `PracticePalLessonsSeeder` to do the actual import
  - Shows import summary

### Seeder (Does the Actual Work)
**File:** `database/seeders/PracticePalLessonsSeeder.php`

#### Key Method:

**`run()`** (Line 15)
- **Main import logic** for bulk lesson import
- **What it does:**
  1. Reads sessions CSV (`data/we speak vocab - sessions.csv`)
     - Parses: `id,grade_level,session_title,title`
     - Extracts session number and grade level
  2. Reads vocabulary CSV (`data/we speak vocab - vocab.csv`)
     - Parses: `session_id,word,hebrew,arabic`
     - Groups vocabulary by session_id
  3. Creates lessons:
     - Checks for duplicates (by slug)
     - Creates lesson record
     - Links vocabulary to lesson
  4. Creates vocabulary records:
     - Uses translations from CSV (if provided)
     - Does NOT auto-translate (unlike CSV vocabulary import)
     - Does NOT auto-generate audio/images (unlike CSV vocabulary import)

#### Helper Methods:

- **`extractSessionNumber(string $sessionTitle)`** (Line 119)
  - Extracts number from "Session 3", "Session 4 - Part A", etc.

- **`extractGradeNumber(string $gradeLevel)`** (Line 131)
  - Extracts number from "7th Grade", "8th Grade", etc.

### CSV Format Expected

**Sessions CSV** (`data/we speak vocab - sessions.csv`):
```csv
id,grade_level,session_title,title
1,7th Grade,Session 3,Making a Volcano
2,7th Grade,Session 4 - Part A,The Scientific Method
```

**Vocabulary CSV** (`data/we speak vocab - vocab.csv`):
```csv
session_id,word,hebrew,arabic
1,science,,
1,experiment,,
1,volcano,,
2,ice,,
2,melts,,
```

### Important Notes

⚠️ **Difference from CSV Vocabulary Import:**
- Bulk lesson import (`ImportPracticePalLessons`) does NOT auto-translate
- Bulk lesson import does NOT auto-generate audio/images
- It only creates the database records
- For auto-translation/audio/images, you need to use the CSV vocabulary import feature in the admin panel

---

## Alternative Import Command

**File:** `app/Console/Commands/ImportLessons.php`

**Purpose:** Imports lessons from JSON export files (not CSV)
- Command: `php artisan lessons:import {file}`
- Used for importing exported lessons (created by `lessons:export`)
- Not related to CSV import

---

## Related Services

### Translation Service
**File:** `app/Services/Translation/OpenAiTranslator.php`
- Used by CSV vocabulary import to auto-translate
- Called in `VocabularyController::processCsv()` (Line 408)

### TTS Service
**File:** `app/Services/Tts/ElevenLabsTtsService.php`
- Used by CSV vocabulary import to generate audio
- Called via `VocabularyController::generateVocabularyAudio()` (Line 442)

### Image Generator Service
**File:** `app/Services/ImageGeneration/ImageGeneratorService.php`
- Used by CSV vocabulary import to generate images
- Called in `VocabularyController::processCsv()` (Line 431)

---

## Summary

### CSV Vocabulary Import (Single Lesson)
- **Entry Point:** Admin panel → `/admin/lessons/{lesson}/vocabulary/csv/upload`
- **Controller:** `VocabularyController::processCsv()`
- **Features:** Auto-translate, auto-audio, auto-images, auto-create games
- **Route:** `POST /admin/lessons/{lesson}/vocabulary/csv/process`

### Bulk Lesson Import (Multiple Lessons)
- **Entry Point:** Command line → `php artisan talma:import-lessons`
- **Command:** `ImportPracticePalLessons::handle()`
- **Seeder:** `PracticePalLessonsSeeder::run()`
- **Features:** Creates lessons and vocabulary, NO auto-translate/audio/images
- **CSV Files:** `data/we speak vocab - sessions.csv` and `data/we speak vocab - vocab.csv`

### Key Difference
- **CSV Vocabulary Import** (admin panel) = Full automation (translations, audio, images, games)
- **Bulk Lesson Import** (command) = Basic import (just creates records, no automation)
