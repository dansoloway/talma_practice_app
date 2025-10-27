# WeSpeak - Production Deployment Guide

## 🚀 Pre-Deployment Checklist

### 1. Environment Configuration
Create a production `.env` file with these changes:

```env
APP_NAME="WeSpeak"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (update with production credentials)
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=wespeak_prod
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# Required API Keys
ELEVENLABS_API_KEY=your-elevenlabs-api-key

# Admin Authentication
ADMIN_PASSWORD=your-secure-admin-password

# Session & Cache (recommended for production)
SESSION_DRIVER=database
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. Database Setup
Run these commands on production server:

```bash
# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Seed initial data (if needed)
php artisan db:seed
```

### 3. File Permissions
Ensure proper permissions:

```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### 4. Optimization Commands
Run these for production performance:

```bash
# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## 📁 Required Directories
Ensure these directories exist and are writable:

```
storage/app/public/tts/words/
storage/app/public/tts/sentences/
storage/app/public/vocabulary-images/
storage/logs/
```

## 🔧 Server Requirements

### PHP Extensions Required:
- PHP 8.1+
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- cURL (for ElevenLabs API)
- GD or Imagick (for image processing)

### Web Server Configuration
#### Apache (.htaccess already included)
#### Nginx Sample Configuration:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/wespeak/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🔐 Security Considerations

### 1. Admin Access
- Change `ADMIN_PASSWORD` to a strong password
- Consider implementing rate limiting for admin login
- Use HTTPS in production

### 2. File Upload Security
- Vocabulary images are validated for type and size
- Files are stored outside web root in `storage/`

### 3. API Keys
- Keep `ELEVENLABS_API_KEY` secure
- Monitor API usage and costs

## 🎵 TTS Audio Files
- Audio files are stored in `storage/app/public/tts/`
- Accessible via `/storage/tts/` URLs
- Consider CDN for better performance
- Monitor storage usage (audio files can accumulate)

## 📊 Monitoring & Maintenance

### Log Files to Monitor:
- `storage/logs/laravel.log` - Application errors
- Web server error logs
- Database slow query logs

### Regular Maintenance:
- Monitor TTS API usage and costs
- Clean up old audio files if needed
- Backup database regularly
- Update dependencies periodically

## 🚨 Troubleshooting

### Common Issues:

1. **"No audio available"**
   - Check ElevenLabs API key
   - Verify TTS files exist in storage
   - Check file permissions

2. **Admin login not working**
   - Verify `ADMIN_PASSWORD` in .env
   - Clear config cache: `php artisan config:clear`

3. **Images not displaying**
   - Run `php artisan storage:link`
   - Check file permissions

4. **Database errors**
   - Run `php artisan migrate --force`
   - Check database credentials

## 🎯 Post-Deployment Testing

Test these key features:
- [ ] Student homepage and grade selection
- [ ] Lesson viewing and vocabulary presentation
- [ ] Sentence completion game with audio
- [ ] Audio recording and playback
- [ ] Admin login and lesson management
- [ ] CSV import for prompts and vocabulary
- [ ] TTS generation for new content

## 📈 Performance Tips

1. **Enable OPcache** in PHP
2. **Use Redis** for sessions and cache
3. **Configure CDN** for static assets
4. **Enable Gzip** compression
5. **Monitor database** query performance
6. **Consider queue workers** for TTS generation

---

## 🎉 You're Ready to Deploy!

The application includes:
- ✅ Complete sentence completion game
- ✅ Audio recording and TTS integration
- ✅ Admin panel with CSV import
- ✅ Student-friendly interface
- ✅ Mobile-responsive design
- ✅ Lesson archiving and management
- ✅ Grade-level organization

Good luck with your deployment! 🚀
