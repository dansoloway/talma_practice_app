# TALMA Practice Pal Project Structure

Complete file listing for the Laravel sentence speaking application.

## Database Layer

### Migrations
- `database/migrations/2024_01_01_000001_create_lessons_table.php`
- `database/migrations/2024_01_01_000002_create_prompts_table.php`
- `database/migrations/2024_01_01_000003_create_options_table.php`
- `database/migrations/2024_01_01_000004_create_prompt_option_assets_table.php`
- `database/migrations/2024_01_01_000005_create_responses_table.php`
- `database/migrations/2024_01_01_000006_create_settings_table.php`

### Seeders
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/LessonSeeder.php` (with 3 sample lessons)

## Models

- `app/Models/Lesson.php` - with prompts() relationship
- `app/Models/Prompt.php` - with options() and assets() relationships
- `app/Models/Option.php` - with assets() relationship
- `app/Models/PromptOptionAsset.php` - junction table with audio paths
- `app/Models/Response.php` - student progress tracking
- `app/Models/Setting.php` - app configuration

## Controllers

### Public Controllers
- `app/Http/Controllers/LessonController.php` - index, show
- `app/Http/Controllers/PromptController.php` - show (JSON API)
- `app/Http/Controllers/PromptModelController.php` - get model sentence + audio
- `app/Http/Controllers/ResponseController.php` - store student responses

### Admin Controllers
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/LessonController.php` - full CRUD
- `app/Http/Controllers/Admin/PromptController.php` - full CRUD
- `app/Http/Controllers/Admin/OptionController.php` - full CRUD

## Routes

- `routes/web.php` - all public and admin routes configured

## Views

### Layouts
- `resources/views/layouts/app.blade.php` - public layout (assistant font)
- `resources/views/layouts/admin.blade.php` - admin layout (secular font for headers)

### Public Views
- `resources/views/lessons/index.blade.php` - lesson list
- `resources/views/lessons/show.blade.php` - lesson runner (interactive)

### Admin Views
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/lessons/index.blade.php`
- `resources/views/admin/lessons/create.blade.php`
- `resources/views/admin/lessons/edit.blade.php`
- `resources/views/admin/lessons/show.blade.php`
- `resources/views/admin/prompts/create.blade.php`
- `resources/views/admin/prompts/edit.blade.php`
- `resources/views/admin/prompts/show.blade.php`
- `resources/views/admin/options/create.blade.php`
- `resources/views/admin/options/edit.blade.php`

## Frontend Assets

- `public/css/app.css` - Complete styles (assistant + secular fonts, responsive)
- `public/js/lesson.js` - Lesson runner (option selection, audio playback, recording)

## Commands

- `app/Console/Commands/BuildTtsAssets.php` - Generate TTS asset records
- `app/Console/Commands/VerifyTtsAssets.php` - Verify audio files exist

## Configuration

- `.env.example` - Environment template with privacy/recording settings
- `README.md` - Full documentation
- `SETUP.md` - Quick start guide

## Key Features Implemented

✅ **Database Schema**
- 6 tables with proper relationships
- Cascade deletes
- Sort ordering
- Privacy-friendly response tracking

✅ **Content Management (Admin)**
- Simple Blade-based admin (no npm needed)
- Full CRUD for lessons, prompts, options
- Dashboard with stats
- Styled with assistant/secular fonts

✅ **Public Interface**
- Lesson browser
- Interactive lesson runner
- Audio playback
- Optional recording (with privacy controls)
- Progress tracking
- Mobile responsive

✅ **TTS Management**
- Pre-generation strategy (no runtime API calls)
- CLI commands to build/verify assets
- Flexible: works with any TTS service
- Storage path management

✅ **Privacy Controls**
- Recording upload can be disabled via .env
- Local-only playback option
- Configurable storage (local/S3)
- Max recording duration limit

## Next Steps

1. Run `composer install`
2. Configure `.env`
3. Run migrations + seeders
4. Generate TTS audio files
5. Add option images to `public/images/`
6. Start serving!

See `SETUP.md` for step-by-step instructions.

