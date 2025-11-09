# Create a New Session/Lesson - Complete Workflow

## Overview
This guide explains the step-by-step process for creating a new lesson/session in TALMA Practice Pal with vocabulary, images, prompts, and games.

---

## Step 1: Create the Lesson/Session

### Option A: Via Admin Panel (Recommended for Manual Creation)
1. Go to: `/admin/lessons` 
2. Click **"Create Lesson"**
3. Fill in the form:
   - **Title** (required) - e.g., "Making a Paper Airplane"
   - **Slug** (optional) - auto-generated from title if left blank
   - **Instructions** (optional) - shown to students at start
   - **Grade Level** - select appropriate grade
   - **Session Number** - e.g., 1, 2, 3
   - **Session Title** - e.g., "Session 3 - Part A"
   - **Active** - checkbox to enable/disable
4. Click **"Create Lesson"**

### Option B: Via CSV Import (For Bulk Creation)
See `LESSON_IMPORT_GUIDE.md` for importing lessons from CSV files.

---

## Step 2: Add Vocabulary Words

### Navigate to Vocabulary Management
1. Go to: `/admin/lessons/{lesson_id}/manage`
2. Find the **Vocabulary** section
3. Click **"Edit Vocabulary"**

### Option A: Add Vocabulary Manually (One by One)
1. Click **"+ Add Vocabulary"**
2. Fill in the form:
   - **English Word** (required)
   - **Hebrew Translation** (optional)
   - **Arabic Translation** (optional)
   - **Image** - upload image file
   - **Sort Order** (optional)
3. Click **"Create"**
   - ✅ Audio is **automatically generated** for the word

### Option B: Upload Vocabulary via CSV
1. Click **"Upload CSV"** button
2. Download the CSV template if needed
3. CSV Format:
   ```csv
   English Word,Hebrew Translation,Arabic Translation
   variable,משתנה,متغير
   experiment,ניסוי,تجربة
   ```
4. Upload your CSV file
5. Choose import mode:
   - **Add** - adds to existing vocabulary
   - **Replace** - replaces all vocabulary

### Option C: Auto-Find Images for All Vocabulary
1. From the manage page, click **"🔍 Auto-Find Images"**
2. The system will show vocabulary without images
3. For each word:
   - Click **"Find Images"**
   - Select from suggested images (Unsplash/Pixabay)
   - Click **"Apply Image"**
4. System downloads and applies the image automatically

### Add Images to Vocabulary (Multiple Methods)
- **Upload**: Click on a vocabulary item → Upload image
- **Auto-Find**: Use the auto-image finder tool
- **Edit**: Go to vocabulary item → Edit → Upload new image

---

## Step 3: Create Prompts (Sentence Completion Activity)

### Navigate to Prompts
1. From `/admin/lessons/{lesson_id}/manage`
2. Find the **Activities** section
3. Click **"Edit"** for Prompts section

### Option A: Create Prompts Manually
1. Click **"+ Add Prompt"**
2. Fill in the form:
   - **Prompt Text** - e.g., "Complete the sentence:"
   - **Template** - e.g., "The ice _____ in the sun." (must contain `{}`)
3. Click **"Create"**
4. Then add options:
   - Click on the prompt to view it
   - Click **"+ Add Option"** for each answer choice
   - Fill in:
     - **Label** - the answer choice (e.g., "melts")
     - **Image** - optional image for the option
     - **Is Correct** - mark the correct answer
   - ✅ Audio is **automatically generated** for each option's:
     - Word audio (the label itself)
     - Sentence audio (template + label combined)

### Option B: Import Prompts via CSV
1. Click **"Import CSV"** from prompts management page
2. CSV Format:
   ```csv
   Prompt Text,Template,Option 1,Option 2,Option 3,Correct
   Complete the sentence,The ice {} in the sun,m freezes,m melts,m boils,2
   What happens?,Water {} when heated,m freezes,m evaporates,m condenses,2
   ```
3. Upload CSV file
4. Choose import mode (Add or Replace)
5. Preview the prompts before importing
6. Click **"Import Prompts"**
   - ✅ Audio is **automatically generated** during import

### Prompt Features
- **Template**: Must contain `{}` placeholder
- **Auto-TTS**: Sentence audio = template + option combined
- **Options**: Multiple choice answers (minimum 2)
- **Correct Answer**: Mark which option is correct

---

## Step 4: Create Games

### Matching Game
1. From `/admin/lessons/{lesson_id}/manage`
2. Click **"+ Matching"** in Activities section
3. Fill in:
   - **Title** - e.g., "Match Pictures to Words"
   - **Game Type** - choose mode
4. System automatically uses vocabulary from the lesson
5. Save the game

### Flashcard Game
1. From `/admin/lessons/{lesson_id}/manage`
2. Click **"+ Flashcard"** in Activities section
3. Fill in:
   - **Title** - e.g., "Practice Vocabulary"
   - **Game Type** - choose mode:
     - Image → Word
     - Image → Audio
     - Audio → Image
     - Audio → Word
4. System automatically uses vocabulary from the lesson
5. Save the game

### Game Types Explained

**Matching Games:**
- Match images to words
- Match audio to words
- Match translations

**Flashcard Games:**
- **Image → Word**: Show image, choose correct word
- **Image → Audio**: Show image, choose correct audio
- **Audio → Image**: Hear audio, choose correct image
- **Audio → Word**: Hear audio, choose correct word (supports Hebrew/Arabic)

---

## Step 5: Arrange Activity Order

1. From `/admin/lessons/{lesson_id}/manage`
2. Find the **Activities** section
3. Drag and drop activities to reorder them
4. Click **"Save Order"** when done

This order determines the sequence students will see activities.

---

## Step 6: Test Student Experience

1. From `/admin/lessons/{lesson_id}/manage`
2. Click **"Play as Student"** button
3. Test all activities:
   - ✅ Prompts (sentence completion)
   - ✅ Matching games
   - ✅ Flashcard games
4. Verify:
   - Audio plays correctly
   - Images display properly
   - Translations are correct
   - Game logic works

---

## Important Notes

### Automatic TTS Generation
- ✅ **Vocabulary**: Generated when word is created
- ✅ **Prompt Options**: Generated when option is created/updated
- ✅ **Prompt Template**: Regenerated for all options if template is edited

### TTS Status Tracking
- **Admin → Prompts**: See "TTS Status" for each prompt
  - "✓ Generated" = audio exists
  - "Not Generated" = no audio
- **Admin → Prompt Detail**: See individual option audio status

### Best Practices
1. **Add vocabulary first** - Games and prompts depend on it
2. **Add images** - Visual learning is more effective
3. **Create prompts** - Sentence completion is the main activity
4. **Test games** - Ensure everything works before publishing
5. **Organize order** - Logical flow for students

### CSV Templates
- Vocabulary: `/admin/lessons/{lesson_id}/vocabulary/csv-template`
- Prompts: Use format shown in import page

---

## Quick Reference: Key Routes

| Action | URL |
|--------|-----|
| List all lessons | `/admin/lessons` |
| Create lesson | `/admin/lessons/create` |
| Manage lesson | `/admin/lessons/{id}/manage` |
| Edit vocabulary | `/admin/lessons/{id}/vocabulary` |
| Auto-find images | `/admin/lessons/{id}/vocabulary/auto-images` |
| Import vocabulary CSV | `/admin/lessons/{id}/vocabulary/csv/upload` |
| Edit prompts | `/admin/lessons/{id}/prompts` |
| Import prompts CSV | `/admin/lessons/{id}/prompts/import` |
| Play as student | `/lessons/{slug}` |

---

## Troubleshooting

### Images Not Showing
- Check file permissions in `storage/app/public/`
- Run `php artisan storage:link`
- Clear browser cache

### Audio Not Playing
- Check TTS generation completed
- Verify `ELEVENLABS_API_KEY` in `.env`
- Check logs: `storage/logs/laravel.log`

### Games Not Working
- Ensure vocabulary has images for Image-based games
- Ensure vocabulary has audio for Audio-based games
- Check that games are marked "Active"

### Import Issues
- Verify CSV format matches template
- Check file size (max 2MB)
- Review validation errors in preview

