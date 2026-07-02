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

### Logging

While the import runs, each lesson is printed with progress `[n/total]` and counts. A detailed log is appended to:

```
storage/logs/summer_practice_pal_import.log
```

Monitor on the server:

```bash
tail -f storage/logs/summer_practice_pal_import.log
```

Entries include timestamps, level (`INFO`, `LESSON`, `WARN`, `ERROR`, `DONE`), and JSON context for each lesson.

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
| `--vocab-csv=*` | Validated vocab CSV (repeatable; `CEFR=path` or filename containing cefr slug). Replaces XLSX `cleaned vocab` for that level. |
| `--prompts-csv=` | Fill-in-the-blank CSV **replaces** the XLSX prompt sheet when set (e.g. A2 or Pre-A1 prompts) |
| `--strict` | Fail dry-run/import when any lesson has fewer than 5 or more than 10 vocab words (requires vocab CSV) |
| `--skip-archive` | With `--force`, skip archiving images/audio before replace |

## Correcting vocabulary and fill-in-the-blank

The XLSX `cleaned vocab` sheet imported activity titles and sentences for some levels (especially A2). Use **per-CEFR vocab CSV files** with 5–10 **single words** per lesson instead.

Expected vocab CSV columns: `CEFR Level`, `Grade Band`, `Day Number`, `Day / Topic`, `Vocabulary Word`, `Definition`

Expected prompts CSV columns: `CEFR Level`, `Grade Band`, `Day Number`, `Day / Topic`, `Question` (must contain `{blank}`), `Answer`

Convention paths (auto-detected when present):

```
data/summer-vocab-pre-a1.csv
data/summer-prompts-pre-a1.csv
data/summer-vocab-a1.csv
data/summer-prompts-a1.csv
data/summer-vocab-a2.csv
data/summer-prompts-a2.csv
data/summer-vocab-b1.csv
data/summer-prompts-b1.csv
```

Pre-A1, A1, A2, and B1 CSV imports use **legacy lesson slugs** (by day number) so `--force` replaces existing production lessons instead of creating duplicates. On `--force`, duplicate lessons outside the validated 15-day set are **permanently deleted**.

To remove leftover inactive lessons from an earlier import:

```bash
php artisan talma:summer-practice-pal-prune-lessons --cefr=Pre-A1
```

### Corrected import workflow

```bash
# 1. Archive paid images/audio before cleanup
php artisan talma:archive-summer-vocab-assets

# 2. Dry-run Pre-A1 with corrected CSVs
php artisan talma:import-summer-practice-pal \
  --cefr=Pre-A1 \
  --force \
  --strict \
  --dry-run

# 3. Apply Pre-A1 (archives assets per lesson before replace unless --skip-archive)
php artisan talma:import-summer-practice-pal \
  --cefr=Pre-A1 \
  --force \
  --strict

# 4. Dry-run / apply A2 (same pattern)
php artisan talma:import-summer-practice-pal \
  --cefr=A2 \
  --force \
  --strict \
  --dry-run

# 4b. Dry-run / apply A1 (same pattern)
php artisan talma:import-summer-practice-pal \
  --cefr=A1 \
  --force \
  --strict \
  --dry-run

php artisan talma:import-summer-practice-pal \
  --cefr=A1 \
  --force \
  --strict

# 4c. Dry-run / apply B1 (same pattern)
php artisan talma:import-summer-practice-pal \
  --cefr=B1 \
  --force \
  --strict \
  --dry-run

php artisan talma:import-summer-practice-pal \
  --cefr=B1 \
  --force \
  --strict

# 5. Audit lesson word counts and missing prompts (all CEFR levels)
php artisan talma:summer-practice-pal-audit --summary --source

# List every vocab word per lesson (save to log)
php artisan talma:summer-practice-pal-audit --list-vocab

# One level only
php artisan talma:summer-practice-pal-audit --cefr=A2 --list-vocab

# 6. Enrichment for new vocab only
php artisan talma:import-summer-practice-pal --with-enrichment

# 7. Coverage report
php artisan talma:summer-practice-pal-coverage --incomplete
```

Archives are written to `storage/app/archived/summer-practice-pal/{timestamp}/` with a `manifest.jsonl` per vocabulary row (original word, lesson slug, image/audio paths).

## Re-import / updates

- **Safe to re-run** (default mode): existing lessons are left unchanged; only **missing** vocabulary words and prompts are added; games are created if missing.
- Use `--force` only to wipe and replace lesson content after spreadsheet changes.
- If courses were previously attached to the default org, re-run import (default detaches automatically) or use `--no-detach-from-default` to opt out.

## Learner flow

1. User visits `/o/summer-practice-pal` → redirected to login.
2. New families register at `/o/summer-practice-pal/register` → **parent/guardian signup** with privacy terms acceptance, parent account, and one or more children.
3. After login, parents with multiple shared-login children choose who is practicing (`/o/summer-practice-pal/select-child`).
4. User sees all four Summer courses on the org course picker.
5. Legacy public URLs (`/lessons/...`, game play URLs) redirect guests to Summer login when the lesson belongs to a restricted-only course.

## Registration settings

Summer Practice Pal uses **parent / guardian signup** (`registration_type = parent_signup`). Global admins can choose registration type per organization under Admin → Organizations.

## Admin notes

- Global admins can still access Summer content for testing via org-scoped URLs when logged in as admin.
- Teacher/admin login remains at `/admin/login` — unchanged.
- Summer org settings: `access_mode = restricted`, `allow_self_registration = true`, `registration_type = parent_signup` (set by import).
