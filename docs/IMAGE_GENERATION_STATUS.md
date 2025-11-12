# Image Generation Status

## Current State

As of November 2024, we have implemented multiple image generation options for vocabulary flashcards, but **automatic image generation is currently disabled** pending Flaticon API key activation.

## Available Image Services

### 1. Flaticon (Primary - Currently Pending)
- **Status**: ⏳ Waiting for API key activation
- **Priority**: Highest (will be used first when enabled)
- **Why**: Perfect for flashcards - simple, clean icons with no text
- **Setup**: 
  - API key configured in `.env`: `FLATICON_API_KEY`
  - Contacted Flaticon support for API key activation
  - Once activated, will automatically search and download icons
- **Implementation**: `app/Services/ImageGeneration/FlaticonImageGenerator.php`
- **API Docs**: https://api.flaticon.com/v3/docs/index.html

### 2. Stock Photo APIs (Fallback)
- **Status**: ✅ Ready (but not configured)
- **Services**: Unsplash, Pixabay
- **Why**: Good fallback if Flaticon doesn't have an icon
- **Setup Required**:
  - `UNSPLASH_ACCESS_KEY` - Get from https://unsplash.com/developers (free: 5,000/month)
  - `PIXABAY_API_KEY` - Get from https://pixabay.com/api/docs/ (free: 5,000/month)
- **Implementation**: `app/Services/ImageGeneration/StockImageGenerator.php`

### 3. Leonardo.ai (AI Generation)
- **Status**: ✅ Ready (but not configured)
- **Why**: AI-generated images, but had issues with text appearing in images
- **Setup Required**: `LEONARDO_API_KEY` from https://app.leonardo.ai/settings/api
- **Cost**: $9/month minimum
- **Implementation**: `app/Services/ImageGeneration/LeonardoImageGenerator.php`

### 4. OpenAI DALL-E (AI Generation)
- **Status**: ✅ Ready (configured, but not preferred)
- **Why**: AI-generated images, but slow and expensive
- **Setup**: Already configured (`OPENAI_API_KEY`)
- **Cost**: ~$0.04-0.12 per image
- **Implementation**: `app/Services/ImageGeneration/OpenAiImageGenerator.php`

## Current Behavior

**Automatic image generation is DISABLED** for:
- CSV imports
- Manual vocabulary creation
- Bulk operations

**Manual image upload is ENABLED**:
- Users can upload images manually via the vocabulary management interface
- Images are stored in `storage/app/public/images/vocabulary/`

**Translation is ENABLED**:
- OpenAI translation still works automatically
- Hebrew and Arabic translations are auto-generated if missing

## Priority Order (When Enabled)

When image generation is re-enabled, the system will try services in this order:

1. **Flaticon** (if API key activated)
2. Stock Photos (Unsplash/Pixabay) (if configured)
3. Leonardo.ai (if configured)
4. OpenAI DALL-E (if configured)

## How to Re-enable Automatic Image Generation

Once Flaticon API key is activated:

1. Test the API key:
   ```bash
   php artisan test:flaticon-image book
   ```

2. If successful, automatic generation will work for:
   - CSV imports
   - New vocabulary creation
   - "Generate Image" button in vocabulary management

3. To enable other services, add their API keys to `.env`:
   ```env
   UNSPLASH_ACCESS_KEY=your-key
   PIXABAY_API_KEY=your-key
   LEONARDO_API_KEY=your-key
   ```

## Manual Image Upload

Users can always upload images manually:

1. Go to Vocabulary management for a lesson
2. Click "Edit" on any vocabulary item
3. Upload an image file
4. Or use the "Auto-image Finder" feature to search and select from Unsplash/Pixabay

## Files Modified

- `app/Services/ImageGeneration/FlaticonImageGenerator.php` - Flaticon integration
- `app/Services/ImageGeneration/StockImageGenerator.php` - Unsplash/Pixabay integration
- `app/Services/ImageGeneration/LeonardoImageGenerator.php` - Leonardo.ai integration
- `app/Services/ImageGeneration/OpenAiImageGenerator.php` - DALL-E integration
- `app/Http/Controllers/Admin/VocabularyController.php` - Image generation logic
- `config/services.php` - Service configurations
- `.env.example` - Configuration examples

## Next Steps

1. ✅ Wait for Flaticon API key activation
2. ✅ Test Flaticon integration once activated
3. ⏳ Consider setting up Unsplash/Pixabay as fallback
4. ⏳ Monitor image quality and user feedback

## Notes

- Flaticon is preferred because it provides simple, clean icons perfect for ESL flashcards
- AI generation (Leonardo/DALL-E) had issues with text appearing in images
- Stock photos are good fallback but may be too complex for simple flashcards
- Manual upload gives users full control over image selection

