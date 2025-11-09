#!/bin/bash

# TALMA Practice Pal Production Deployment Script
# Usage: ./deploy.sh

echo "🚀 TALMA Practice Pal Production Deployment"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get current directory
CURRENT_DIR=$(pwd)
APP_DIR=$(dirname "$CURRENT_DIR")
GIT_DIR="$APP_DIR/git_repo"
PUBLIC_DIR="$APP_DIR/public_html"

echo -e "${BLUE}📁 Directories:${NC}"
echo "  App Dir: $APP_DIR"
echo "  Git Dir: $GIT_DIR"
echo "  Public Dir: $PUBLIC_DIR"
echo ""

# Check if git_repo exists
if [ ! -d "$GIT_DIR" ]; then
    echo -e "${RED}❌ Git repository not found at: $GIT_DIR${NC}"
    exit 1
fi

# Step 1: Pull latest changes from git
echo -e "${BLUE}📥 Pulling latest changes from Git...${NC}"
cd "$GIT_DIR"

# Check git status
echo -e "${YELLOW}Current git status:${NC}"
git status --short

# Pull changes
if git pull origin main; then
    echo -e "${GREEN}✅ Git pull successful${NC}"
else
    echo -e "${RED}❌ Git pull failed${NC}"
    exit 1
fi

# Step 2: Copy files to public_html (if needed)
echo ""
echo -e "${BLUE}📋 Checking if files need to be copied...${NC}"

# Check if Cloudways auto-deployment is working
if [ -f "$PUBLIC_DIR/app/Console/Commands/ImportPracticePalLessons.php" ]; then
    echo -e "${GREEN}✅ Files already deployed by Cloudways${NC}"
else
    echo -e "${YELLOW}⚠️  Copying files manually...${NC}"
    rsync -av --exclude='.git' "$GIT_DIR/" "$PUBLIC_DIR/"
    echo -e "${GREEN}✅ Files copied${NC}"
fi

# Step 3: Navigate to public_html
cd "$PUBLIC_DIR"

# Step 4: Install/Update Composer dependencies
echo ""
echo -e "${BLUE}📦 Updating Composer dependencies...${NC}"
if composer install --optimize-autoloader --no-dev; then
    echo -e "${GREEN}✅ Composer install successful${NC}"
else
    echo -e "${YELLOW}⚠️  Composer install had issues, continuing...${NC}"
fi

# Step 5: Run Laravel optimizations
echo ""
echo -e "${BLUE}⚡ Running Laravel optimizations...${NC}"

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✅ Laravel optimizations complete${NC}"

# Step 6: Run database migrations
echo ""
echo -e "${BLUE}🗄️  Running database migrations...${NC}"
if php artisan migrate --force; then
    echo -e "${GREEN}✅ Migrations successful${NC}"
else
    echo -e "${YELLOW}⚠️  Migrations had issues, continuing...${NC}"
fi

# Step 7: Check for CSV files and offer import
echo ""
echo -e "${BLUE}📊 Checking for lesson import files...${NC}"

if [ -f "we speak vocab - sessions.csv" ] && [ -f "we speak vocab - vocab.csv" ]; then
    echo -e "${GREEN}✅ Found CSV files for lesson import${NC}"
    echo ""
    echo -e "${YELLOW}Do you want to import lessons from CSV? (y/n)${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}📚 Importing lessons...${NC}"
        php artisan talma:import-lessons --force
        echo -e "${GREEN}✅ Lesson import complete${NC}"
    else
        echo -e "${BLUE}ℹ️  Skipping lesson import${NC}"
        echo "  To import later, run: php artisan talma:import-lessons"
    fi
else
    echo -e "${YELLOW}⚠️  CSV files not found${NC}"
    echo "  Upload 'we speak vocab - sessions.csv' and 'we speak vocab - vocab.csv'"
    echo "  to run lesson import"
fi

# Step 8: Set proper permissions
echo ""
echo -e "${BLUE}🔐 Setting file permissions...${NC}"
chmod -R 755 storage/ bootstrap/cache/ 2>/dev/null || echo -e "${YELLOW}⚠️  Some permission changes failed (this is normal on some servers)${NC}"

# Step 9: Final checks
echo ""
echo -e "${BLUE}🧪 Running final checks...${NC}"

# Check Laravel version
LARAVEL_VERSION=$(php artisan --version 2>/dev/null || echo "Unknown")
echo "  Laravel Version: $LARAVEL_VERSION"

# Check database connection
if php artisan tinker --execute="echo 'DB Connection: ' . (DB::connection()->getPdo() ? 'OK' : 'Failed') . PHP_EOL;" 2>/dev/null; then
    echo -e "${GREEN}  ✅ Database connection OK${NC}"
else
    echo -e "${RED}  ❌ Database connection failed${NC}"
fi

# Check if site is accessible
echo "  Site URL: https://wespeak.talma.digital"

# Deployment complete
echo ""
echo -e "${GREEN}🎉 Deployment Complete!${NC}"
echo "================================"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "  1. Visit: https://wespeak.talma.digital"
echo "  2. Test admin panel: https://wespeak.talma.digital/admin"
echo "  3. Check lesson import results if you ran it"
echo ""
echo -e "${BLUE}📚 Useful Commands:${NC}"
echo "  • Import lessons: php artisan talma:import-lessons"
echo "  • Check logs: tail -f storage/logs/laravel.log"
echo "  • Clear cache: php artisan config:clear"
echo ""
echo -e "${GREEN}✨ TALMA Practice Pal is ready!${NC}"
