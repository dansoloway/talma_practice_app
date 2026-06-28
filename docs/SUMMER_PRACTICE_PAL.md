# Summer Practice Pal — Setup & Production Runbook

Summer Practice Pal is a **separate gated program** within Practice Pal. Learners must register or sign in; once authenticated they can access all four CEFR courses (Pre-A1, A1, A2, B1).

## URLs

| URL | Purpose |
|-----|---------|
| `/o/summer-practice-pal` | Course picker (login required) |
| `/o/summer-practice-pal/register` | Open self-registration |
| `/o/summer-practice-pal/login` | Sign in |

Summer courses are **not** listed on the public TALMA Community Resources homepage.

## What runs where

| Step | Command / action | When |
|------|------------------|------|
| Deploy code | `git pull` or your deploy pipeline | Each release |
| Database schema | `php artisan migrate --force` | Once per release with migrations |
| Content import | `php artisan talma:import-summer-practice-pal` | **Once** (re-run is idempotent) |
| Self-registration | Automatic after deploy + migrate | No artisan command |

The import command creates the **Summer Practice Pal** organization (`restricted`, self-registration enabled) and attaches courses to that org and Root — **not** to TALMA Community Resources.

## Production checklist (first launch)

```bash
# 1. Deploy application code
git pull origin main

# 2. Install dependencies & migrate
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan storage:link

# 3. Import lesson structure (fast, no API calls — safe to re-run)
php artisan talma:import-summer-practice-pal

# Optional: import one CEFR level first
# php artisan talma:import-summer-practice-pal --cefr=Pre-A1

# Later: add translations, images, and TTS when API credits are available
# php artisan talma:import-summer-practice-pal --with-enrichment

# 4. Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Required environment variables for enrichment import

Only needed with `--with-enrichment`:

- `OPENAI_API_KEY` — Hebrew/Arabic translations
- Image provider keys (see `IMAGE_PROVIDERS` in `.env`)
- `ELEVENLABS_API_KEY` — vocabulary and prompt audio

## Import flags

```bash
php artisan talma:import-summer-practice-pal [options]
```

**Default:** structure only — courses, lessons, vocabulary, games, and fill-in-the-blank prompts. No API calls. **Safe to re-run.**

| Flag | Description |
|------|-------------|
| `--dry-run` | Count courses/lessons only; no DB writes |
| `--cefr=Pre-A1` | Import a single CEFR level |
| `--with-enrichment` | Enable translations, images, and TTS (slow; uses API credits) |
| `--skip-translations` | With `--with-enrichment`, skip Hebrew/Arabic |
| `--skip-images` | With `--with-enrichment`, skip images |
| `--skip-tts` | With `--with-enrichment`, skip audio |
| `--force` | **Destructive:** delete and replace all vocab/prompts on existing lessons |
| `--no-detach-from-default` | Keep courses on TALMA Community Resources (not recommended) |

## Re-import / updates

- **Safe to re-run** (default mode): existing lessons are left unchanged; only **missing** vocabulary words and prompts are added; games are created if missing.
- Use `--force` only to wipe and replace lesson content after spreadsheet changes.
- If courses were previously attached to the default org, re-run import (default detaches automatically) or use `--no-detach-from-default` to opt out.

## Learner flow

1. User visits `/o/summer-practice-pal` → redirected to login.
2. User registers at `/o/summer-practice-pal/register` → account created with `student` role and org membership.
3. User sees all four Summer courses on the org course picker.
4. Legacy public URLs (`/lessons/...`, game play URLs) redirect guests to Summer login when the lesson belongs to a restricted-only course.

## Admin notes

- Global admins can still access Summer content for testing via org-scoped URLs when logged in as admin.
- Teacher/admin login remains at `/admin/login` — unchanged.
- Summer org settings: `access_mode = restricted`, `allow_self_registration = true` (set by import).
