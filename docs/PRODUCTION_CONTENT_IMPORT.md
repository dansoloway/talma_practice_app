# Importing Production Content for Local Testing

This guide explains how to get production content (7th and 8th grade science lessons) into your local development environment to test which games work best with the actual content.

---

## 🎯 Recommended Approach: Artisan Commands

**Best Option:** Use the built-in export/import commands. This is:
- ✅ Most secure (no API endpoints exposed)
- ✅ Most reliable (handles all relationships)
- ✅ Most flexible (can filter by grade, export specific lessons)
- ✅ Works offline (export file can be transferred)

---

## 📤 Step 1: Export from Production

### Option A: SSH into Production Server

```bash
# SSH into your production server
ssh user@your-production-server.com

# Navigate to your application directory
cd /path/to/talma-practice-pal

# Export 7th grade lessons
php artisan lessons:export --grade=7 --output=storage/exports/grade7-lessons.json

# Export 8th grade lessons  
php artisan lessons:export --grade=8 --output=storage/exports/grade8-lessons.json

# Or export both grades together
php artisan lessons:export --grade=7 --output=storage/exports/grade7-lessons.json
php artisan lessons:export --grade=8 --output=storage/exports/grade8-lessons.json

# Download the files to your local machine
# From your local terminal:
scp user@your-production-server.com:/path/to/talma-practice-pal/storage/exports/grade7-lessons.json ./data/
scp user@your-production-server.com:/path/to/talma-practice-pal/storage/exports/grade8-lessons.json ./data/
```

### Option B: Direct Database Export (Alternative)

If you have direct database access, you can export directly:

```bash
# On production server, export to SQL
mysqldump -u username -p database_name lessons parts vocabulary prompts options matching_games flashcard_games > production-export.sql

# Then import locally
mysql -u root -p local_database < production-export.sql
```

**Note:** This method doesn't handle file paths/images/audio well. Use the JSON export instead.

---

## 📥 Step 2: Import into Local Environment

### Import the JSON files:

```bash
# Import 7th grade lessons
php artisan lessons:import data/grade7-lessons.json

# Import 8th grade lessons
php artisan lessons:import data/grade8-lessons.json

# Or import both
php artisan lessons:import data/grade7-lessons.json
php artisan lessons:import data/grade8-lessons.json
```

### Import Options:

```bash
# Skip lessons that already exist (won't overwrite)
php artisan lessons:import data/grade7-lessons.json --skip-existing

# Force import (will overwrite existing lessons)
php artisan lessons:import data/grade7-lessons.json --force

# Dry run (see what would be imported without actually importing)
php artisan lessons:import data/grade7-lessons.json --dry-run
```

---

## 🖼️ Step 3: Handle Media Files (Images & Audio)

The JSON export includes file paths, but the actual files need to be copied separately.

### Option A: Copy Storage Directory (Recommended)

```bash
# On production server, create a tarball of storage files
cd /path/to/talma-practice-pal
tar -czf storage-media.tar.gz storage/app/public/

# Download to local machine
scp user@production:/path/to/storage-media.tar.gz ./

# Extract locally
tar -xzf storage-media.tar.gz -C storage/app/public/
```

### Option B: Sync Specific Directories

```bash
# Sync vocabulary images
rsync -avz user@production:/path/to/storage/app/public/vocabulary-images/ ./storage/app/public/vocabulary-images/

# Sync TTS audio
rsync -avz user@production:/path/to/storage/app/public/tts/ ./storage/app/public/tts/

# Sync option images
rsync -avz user@production:/path/to/storage/app/public/images/ ./storage/app/public/images/
```

### Option C: Download via S3/Cloud Storage

If you're using cloud storage (S3, etc.), download files using your cloud provider's tools.

---

## 🔍 Step 4: Verify Import

```bash
# Check what was imported
php artisan tinker

# In tinker:
>>> \App\Models\Lesson::where('grade_level', 7)->count()
>>> \App\Models\Lesson::where('grade_level', 8)->count()
>>> \App\Models\Vocabulary::count()
>>> \App\Models\Prompt::count()
```

Or check in your admin panel at `/admin/lessons`

---

## 🌐 Alternative: API Endpoint (Less Secure)

If you prefer an API endpoint, you can create one, but it's less secure and requires authentication.

### Create Export Endpoint:

```php
// routes/web.php or routes/api.php
Route::get('/api/export/lessons', function(Request $request) {
    // Require admin authentication
    if (!session('admin_authenticated')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    $grade = $request->get('grade');
    $command = \Illuminate\Support\Facades\Artisan::call('lessons:export', [
        '--grade' => $grade,
        '--output' => storage_path('exports/api-export-' . now()->format('Y-m-d-His') . '.json')
    ]);
    
    $output = \Illuminate\Support\Facades\Artisan::output();
    $file = storage_path('exports/api-export-' . now()->format('Y-m-d-His') . '.json');
    
    return response()->download($file)->deleteFileAfterSend();
})->middleware('admin.auth');
```

**Security Concerns:**
- API endpoints can be rate-limited or abused
- File downloads can be large
- Requires careful authentication
- Not recommended for production use

---

## 📊 What Gets Exported

The export includes:
- ✅ Lesson metadata (title, grade, session, etc.)
- ✅ Parts (lesson sections)
- ✅ Vocabulary words (with translations, image paths, audio paths)
- ✅ Prompts (sentence completion questions)
- ✅ Options (answer choices for prompts)
- ✅ Matching games configuration
- ✅ Flashcard games configuration
- ✅ Vocabulary presentations

**Note:** File paths are exported, but actual files (images/audio) need to be copied separately.

---

## 🎮 Testing Games with Production Content

Once imported, you can:

1. **View lessons locally:**
   ```
   http://localhost:8000/lessons
   ```

2. **Test each game type:**
   - Sentence Completion (Prompts)
   - Matching Games
   - Flashcard Games

3. **Analyze content:**
   - What vocabulary words are used?
   - What types of sentences/prompts?
   - What science topics?
   - How complex is the language?

4. **Determine which new games would work:**
   - Do prompts have clear correct answers? → Spelling game
   - Are there science definitions? → True/False game
   - Are there process steps? → Sequencing game
   - Are there categories? → Category sorting game

---

## 🚀 Quick Start Script

Create a script to automate the process:

```bash
#!/bin/bash
# scripts/import-production-content.sh

echo "📥 Importing Production Content"
echo "================================"

# Export from production (run this on production server)
# ssh user@production "cd /path/to/app && php artisan lessons:export --grade=7 --output=storage/exports/grade7.json"

# Download files (run this locally)
# scp user@production:/path/to/app/storage/exports/grade7.json ./data/
# scp user@production:/path/to/app/storage/exports/grade8.json ./data/

# Import locally
php artisan lessons:import data/grade7.json --skip-existing
php artisan lessons:import data/grade8.json --skip-existing

echo "✅ Import complete!"
echo "📊 Check your lessons at: http://localhost:8000/admin/lessons"
```

---

## 🔒 Security Best Practices

1. **Never expose export endpoints publicly** - Use SSH/command line instead
2. **Use authentication** - If using API, require admin auth
3. **Limit file sizes** - Large exports can timeout
4. **Clean up exports** - Delete export files after downloading
5. **Use secure transfer** - Always use SCP/SFTP, never FTP

---

## 📝 Troubleshooting

### Import fails with "Duplicate entry"
```bash
# Use --skip-existing to skip duplicates
php artisan lessons:import data/lessons.json --skip-existing

# Or --force to overwrite
php artisan lessons:import data/lessons.json --force
```

### Images/audio not showing
- Check that storage files were copied
- Run `php artisan storage:link`
- Check file permissions: `chmod -R 755 storage/`

### Import is slow
- Import one grade at a time
- Use `--skip-existing` if re-importing
- Consider importing without media first, then syncing files separately

---

## 🎯 Next Steps

After importing production content:

1. **Review the content structure:**
   - What science topics are covered?
   - What vocabulary complexity?
   - What sentence structures?

2. **Test existing games:**
   - Do prompts work well?
   - Do matching games make sense?
   - Do flashcards work?

3. **Identify gaps:**
   - What content doesn't fit existing games?
   - What new games would work better?
   - What skills need more practice?

4. **Plan new games:**
   - Based on actual content, not assumptions
   - Focus on what students need
   - Test with real content before building

---

*This process ensures you're building games that work with your actual 7th and 8th grade science content, not just theoretical games.*

