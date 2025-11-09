# Quick Setup Guide

## Initial Setup (5 minutes)

### 1. Install & Configure

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=wespeak
# DB_USERNAME=root
# DB_PASSWORD=your_password
```

### 2. Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE wespeak;"

# Run migrations and seed sample data
php artisan migrate
php artisan db:seed
```

### 3. Storage

```bash
# Link public storage
php artisan storage:link

# Create directories
mkdir -p storage/app/public/tts/lesson1
mkdir -p storage/app/public/images/colors
mkdir -p storage/app/public/images/animals
mkdir -p storage/app/public/images/food
```

### 4. Test

```bash
# Start server
php artisan serve

# Visit http://localhost:8000
```

## Adding Content

### Generate TTS Assets

```bash
# Create database records for all prompt/option combos
php artisan tts:build-assets

# Output shows what audio files to generate
# Example: "My favorite color is red." → storage/app/public/tts/lesson1/p1_o1.mp3
```

### Add Audio Files

Place MP3 files in the paths shown by the command above, then verify:

```bash
php artisan tts:verify
```

### Add Images

Place images in `public/images/` matching the paths in your options:
- `public/images/colors/red.png`
- `public/images/animals/dog.png`
- etc.

## Admin Access

Visit `/admin` to manage:
- Lessons
- Prompts
- Options

No authentication is configured by default. Add Laravel Breeze/Jetstream if needed.

## Quick Deploy

For simple deployment via git:

```bash
# On server
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
```

## Need Help?

See `README.md` for detailed documentation.

