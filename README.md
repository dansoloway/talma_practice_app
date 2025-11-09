# TALMA Practice Pal - Sentence Speaking Practice App

A Laravel + MySQL application for practicing sentence construction through interactive prompts with pre-generated audio.

## Features

- ✅ Lesson management with prompts and options
- ✅ Pre-generated TTS audio (ElevenLabs compatible)
- ✅ Student recording with privacy controls
- ✅ Simple Blade-based admin interface
- ✅ Mobile-friendly design
- ✅ Progress tracking

## Requirements

- PHP 8.1+
- MySQL 8.0+
- Composer
- Web server (Apache/Nginx)

## Installation

### 1. Clone or Copy Files

```bash
# If using git
git clone <repository-url> talma-practice-pal
cd talma-practice-pal

# Or just copy the files into your project directory
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Setup

```bash
# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with your database credentials
# DB_DATABASE=wespeak
# DB_USERNAME=root
# DB_PASSWORD=your_password
```

### 4. Database Setup

```bash
# Create the database
mysql -u root -p
CREATE DATABASE wespeak;
exit;

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed
```

### 5. Storage Setup

```bash
# Create symbolic link for public storage
php artisan storage:link

# Create TTS directories
mkdir -p storage/app/public/tts
mkdir -p storage/app/public/images
mkdir -p storage/app/private/recordings
```

### 6. Serve the Application

```bash
# Development server
php artisan serve

# Visit: http://localhost:8000
```

## Content Management

### Admin Panel

Access the admin panel at `/admin` to:
- Create and manage lessons
- Add prompts with templates
- Configure options with images
- View student responses

### Admin Routes

- `/admin` - Dashboard
- `/admin/lessons` - Lesson management
- `/admin/lessons/{lesson}/prompts/create` - Add prompts
- `/admin/prompts/{prompt}/options/create` - Add options

## TTS Audio Generation

### 1. Build Asset Records

```bash
# Generate database records for all prompt/option combinations
php artisan tts:build-assets

# For a specific lesson only
php artisan tts:build-assets --lesson=colors

# Dry run (preview without creating)
php artisan tts:build-assets --dry-run
```

This command will:
- Generate sentence text for each (prompt, option) combination
- Create database records in `prompt_option_assets`
- Tell you which audio files need to be generated

### 2. Generate Audio Files

You have several options:

#### Option A: ElevenLabs API (Recommended)

Create a script to batch-generate audio:

```php
// scripts/generate-tts.php
$assets = PromptOptionAsset::whereNull('duration_ms')->get();

foreach ($assets as $asset) {
    $response = Http::withHeaders([
        'xi-api-key' => env('ELEVENLABS_API_KEY')
    ])->post('https://api.elevenlabs.io/v1/text-to-speech/VOICE_ID', [
        'text' => $asset->generated_sentence,
        'model_id' => 'eleven_monolingual_v1'
    ]);
    
    $path = storage_path("app/public" . str_replace('/storage', '', $asset->audio_path));
    file_put_contents($path, $response->body());
    
    // Update duration if needed
    $asset->update(['duration_ms' => 3000]); // Estimate or calculate
}
```

#### Option B: Manual Upload

1. Export sentences from database
2. Use any TTS service (ElevenLabs, Google TTS, AWS Polly, etc.)
3. Name files according to pattern: `p{prompt_id}_o{option_id}.mp3`
4. Upload to `storage/app/public/tts/lesson{id}/`

#### Option C: External Script

Place pre-generated MP3 files in the correct directories and the app will use them.

### 3. Verify Assets

```bash
# Check that all audio files exist
php artisan tts:verify
```

This will report any missing audio files.

## Privacy & Recording Settings

Edit `.env` to control student recordings:

```env
# Disable uploads (default) - recordings stay in browser only
PRIVACY_ALLOW_UPLOAD=false

# Enable uploads - save recordings to server
PRIVACY_ALLOW_UPLOAD=true

# Max recording duration in seconds
RECORDING_MAX_SECONDS=20

# Where to store recordings
RECORDING_DISK=local  # or 's3'
```

**Important:** Recordings are optional. Students can practice without recording, or record locally for playback without uploading to the server.

## Deployment

### Production Checklist

1. **Environment**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Optimize**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Permissions**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

4. **Web Server**
   - Point document root to `/public`
   - Configure `.htaccess` (Apache) or nginx config
   - Enable HTTPS

5. **Assets**
   - Upload images to `public/images/`
   - Ensure TTS files are in `storage/app/public/tts/`
   - Run `php artisan storage:link`

## File Structure

```
talma-practice-pal/
├── app/
│   ├── Console/Commands/
│   │   ├── BuildTtsAssets.php      # Generate TTS asset records
│   │   └── VerifyTtsAssets.php     # Check audio files exist
│   ├── Http/Controllers/
│   │   ├── LessonController.php    # Public lesson display
│   │   ├── PromptController.php    # Prompt API
│   │   ├── PromptModelController.php  # Model sentence API
│   │   ├── ResponseController.php  # Save student responses
│   │   └── Admin/                  # Admin controllers
│   └── Models/                     # Eloquent models
├── database/
│   ├── migrations/                 # Database schema
│   └── seeders/                    # Sample data
├── public/
│   ├── css/app.css                 # Styles
│   ├── js/lesson.js                # Lesson runner
│   └── images/                     # Option images
├── resources/views/
│   ├── layouts/                    # Base layouts
│   ├── lessons/                    # Public views
│   └── admin/                      # Admin views
├── routes/web.php                  # Application routes
└── storage/app/public/
    └── tts/                        # Pre-generated audio files
```

## Adding New Lessons

### Via Admin Panel

1. Go to `/admin/lessons/create`
2. Enter title (slug auto-generates)
3. Add prompts with templates (use `{{answer}}` placeholder)
4. Add options with image paths
5. Run `php artisan tts:build-assets --lesson=your-slug`
6. Generate audio files
7. Run `php artisan tts:verify`

### Via Seeder

Edit `database/seeders/LessonSeeder.php`:

```php
$lesson = Lesson::create([
    'title' => 'Favorite Sports',
    'slug' => 'sports',
    'is_active' => true,
    'sort_order' => 4,
]);

$prompt = Prompt::create([
    'lesson_id' => $lesson->id,
    'prompt_text' => 'What is your favorite sport?',
    'template' => 'My favorite sport is {{answer}}.',
    'tts_voice' => 'default',
    'sort_order' => 1,
]);

$sports = ['soccer', 'basketball', 'swimming', 'tennis'];
foreach ($sports as $index => $sport) {
    Option::create([
        'prompt_id' => $prompt->id,
        'label' => $sport,
        'image_path' => "images/sports/{$sport}.png",
        'is_active' => true,
        'sort_order' => $index + 1,
    ]);
}
```

Then run:
```bash
php artisan db:seed --class=LessonSeeder
php artisan tts:build-assets --lesson=sports
```

## Troubleshooting

### Audio Not Playing

- Check `php artisan storage:link` was run
- Verify files exist in `storage/app/public/tts/`
- Check browser console for 404 errors
- Ensure file permissions are correct (755 for directories, 644 for files)

### Recording Not Working

- Check browser microphone permissions
- Test in different browsers (Safari iOS has different audio format)
- Verify HTTPS is enabled (required for getUserMedia in production)

### Missing Assets

```bash
php artisan tts:verify
```

Will show which audio files are missing.

## Browser Compatibility

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+ (iOS 14.5+)
- ⚠️ Safari uses MP4/AAC for recordings (not WebM)

## License

This application is open-source software.

## Support

For issues or questions, refer to the Laravel documentation at https://laravel.com/docs

