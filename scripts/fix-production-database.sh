#!/bin/bash

# TALMA Practice Pal Production Database Fix Script
# This script runs the migration to add translation columns, then resets lessons

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

echo "🔧 TALMA Practice Pal Production Database Fix"
echo "=================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from the Laravel project root directory"
    exit 1
fi

echo "📊 Checking current database status..."

# Check if translation columns exist
php artisan tinker --execute="
try {
    \$hasHebrew = \Schema::hasColumn('vocabulary', 'hebrew_translation');
    \$hasArabic = \Schema::hasColumn('vocabulary', 'arabic_translation');
    echo 'Hebrew column exists: ' . (\$hasHebrew ? 'Yes' : 'No') . PHP_EOL;
    echo 'Arabic column exists: ' . (\$hasArabic ? 'Yes' : 'No') . PHP_EOL;
} catch (Exception \$e) {
    echo 'Error checking columns: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔄 Running database migrations..."

# Run migrations to add the missing columns
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Error: Failed to run migrations"
    exit 1
fi

echo "✅ Migrations completed"
echo ""

# Verify columns were added
php artisan tinker --execute="
\$hasHebrew = \Schema::hasColumn('vocabulary', 'hebrew_translation');
\$hasArabic = \Schema::hasColumn('vocabulary', 'arabic_translation');
echo 'After migration:' . PHP_EOL;
echo 'Hebrew column exists: ' . (\$hasHebrew ? 'Yes' : 'No') . PHP_EOL;
echo 'Arabic column exists: ' . (\$hasArabic ? 'Yes' : 'No') . PHP_EOL;
"

echo ""
echo "🎉 Database fix completed!"
echo "   You can now run ./scripts/reset-lessons.sh to import the lessons with translations."
