# Production Deployment Checklist

## Pre-Deployment Steps

### 1. Commit All Changes
```bash
# Review changes
git status

# Add all changes
git add .

# Commit with descriptive message
git commit -m "Add Flaticon integration, disable auto image generation, update translations"

# Push to remote
git push origin main
```

### 2. Review Changes Being Deployed
- ✅ Flaticon image generation service (disabled for now)
- ✅ Stock image generators (Unsplash/Pixabay) - ready but not configured
- ✅ Leonardo.ai integration - ready but not configured
- ✅ Automatic image generation **DISABLED** (translations still work)
- ✅ Manual image upload still works
- ✅ Translation improvements
- ✅ New vocabulary image management features

### 3. Check for New Migrations
```bash
# Check if there are any new migrations
php artisan migrate:status
```

**Note**: No new migrations in this deployment - all changes are code-only.

## Server-Side Deployment Steps

### 1. SSH into Production Server
```bash
ssh your-server
cd /path/to/talma_practice_pal
```

### 2. Pull Latest Changes
```bash
# If using git repository
cd git_repo  # or wherever your git repo is
git pull origin main

# Or use the deployment script
./scripts/deploy.sh
```

### 3. Update Composer Dependencies
```bash
cd public_html  # or your app directory
composer install --optimize-autoloader --no-dev
```

### 4. Run Laravel Optimizations
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Update Environment Variables (if needed)

**New optional variables** (not required, but available):
```env
# Image generation APIs (optional - currently disabled)
FLATICON_API_KEY=          # When Flaticon activates your API key
UNSPLASH_ACCESS_KEY=       # Optional fallback
PIXABAY_API_KEY=           # Optional fallback
LEONARDO_API_KEY=          # Optional AI generation
OPENAI_IMAGE_MODEL=dall-e-3  # Already exists
```

**Note**: These are optional. The app works fine without them since auto image generation is disabled.

### 6. Verify Storage Permissions
```bash
chmod -R 755 storage/ bootstrap/cache/
chown -R www-data:www-data storage/ bootstrap/cache/  # Adjust user as needed
```

### 7. Test Critical Features

**Must Test:**
- [ ] Admin login works
- [ ] Vocabulary CSV import works (translations should auto-generate)
- [ ] Manual vocabulary creation works (translations should auto-generate)
- [ ] Manual image upload works
- [ ] Student-facing pages load correctly
- [ ] Translations are being generated (check logs)

**Optional Tests** (if APIs configured):
- [ ] "Generate Image" button works (if Flaticon API activated)
- [ ] Image modal/viewer works

## Post-Deployment Verification

### 1. Check Logs
```bash
tail -f storage/logs/laravel.log
```

Look for:
- ✅ Translation requests working
- ⚠️ Any image generation errors (should be none since it's disabled)
- ⚠️ Any API authentication errors

### 2. Test Translation Flow
1. Create a new vocabulary word without translations
2. Verify Hebrew/Arabic translations are auto-generated
3. Check logs to confirm OpenAI API calls

### 3. Test Image Upload
1. Upload a vocabulary CSV
2. Verify translations work
3. Verify images are NOT auto-generated (as expected)
4. Manually upload an image for a vocabulary word
5. Verify image displays correctly

## Rollback Plan (if needed)

If something goes wrong:

```bash
# Revert to previous commit
cd git_repo
git log  # Find previous commit hash
git checkout <previous-commit-hash>

# Re-run deployment steps
cd ../public_html
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Important Notes

### ✅ What Works Now
- **Translations**: Fully automatic via OpenAI
- **Manual image upload**: Works perfectly
- **CSV imports**: Work with auto-translation
- **All existing features**: Unchanged

### ⏸️ What's Disabled
- **Automatic image generation**: Disabled (can be re-enabled when Flaticon API is ready)
- **AI image generation**: Available but not automatic

### 🔄 What Can Be Enabled Later
Once Flaticon API key is activated:
1. Test: `php artisan test:flaticon-image book`
2. Uncomment image generation code in `VocabularyController.php`
3. Automatic image generation will resume

## Deployment Command Summary

**Quick deployment** (if using deploy script):
```bash
./scripts/deploy.sh
```

**Manual deployment**:
```bash
cd git_repo && git pull origin main
cd ../public_html
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force  # Only if there are new migrations
```

## Expected Behavior After Deployment

1. **CSV Import**: 
   - ✅ Translations auto-generate
   - ✅ Images do NOT auto-generate
   - ✅ Users can upload images manually

2. **Manual Vocabulary Creation**:
   - ✅ Translations auto-generate
   - ✅ Images do NOT auto-generate
   - ✅ Users can upload images manually

3. **"Generate Image" Button**:
   - ⏳ Will work once Flaticon API is activated
   - Currently will show "No image service configured"

## Support

If you encounter issues:
1. Check `storage/logs/laravel.log`
2. Verify `.env` file has correct API keys
3. Test translations manually: Create a vocabulary word and check if translations appear
4. Verify file permissions on `storage/` directory

---

**Ready to deploy?** Follow the steps above, and you're good to go! 🚀

