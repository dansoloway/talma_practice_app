#!/bin/bash

# TALMA Practice Pal Production Reset Script
# This script deletes all lessons and vocabulary, then re-imports from CSV files

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR" || exit 1

echo "🚀 TALMA Practice Pal Production Reset Script"
echo "=================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from the Laravel project root directory"
    echo "   Make sure you're in the directory containing artisan file"
    exit 1
fi

# Check if CSV files exist
DATA_DIR="data"
SESSIONS_CSV="$DATA_DIR/we speak vocab - sessions.csv"
VOCAB_CSV="$DATA_DIR/we speak vocab - vocab.csv"

if [ ! -f "$SESSIONS_CSV" ]; then
    echo "❌ Error: Sessions CSV file not found: $SESSIONS_CSV"
    echo "   Please make sure the CSV files are in the $DATA_DIR/ directory"
    exit 1
fi

if [ ! -f "$VOCAB_CSV" ]; then
    echo "❌ Error: Vocabulary CSV file not found: $VOCAB_CSV"
    echo "   Please make sure the CSV files are in the $DATA_DIR/ directory"
    exit 1
fi

echo "📁 Found CSV files:"
echo "  ✓ $SESSIONS_CSV"
echo "  ✓ $VOCAB_CSV"
echo ""

# Confirm before proceeding
echo "⚠️  WARNING: This will delete ALL existing lessons and vocabulary!"
echo "   This action cannot be undone."
echo ""
read -p "Are you sure you want to continue? (type 'yes' to confirm): " confirmation

if [ "$confirmation" != "yes" ]; then
    echo "❌ Operation cancelled."
    exit 0
fi

echo ""
echo "🗑️  Deleting all existing data..."

# Delete all data in correct order (respecting foreign key constraints)
php artisan tinker --execute="
echo 'Deleting vocabulary...' . PHP_EOL;
\App\Models\Vocabulary::query()->delete();
echo 'Deleting prompts and options...' . PHP_EOL;
\App\Models\Option::query()->delete();
\App\Models\Prompt::query()->delete();
echo 'Deleting matching games...' . PHP_EOL;
\App\Models\MatchingGame::query()->delete();
echo 'Deleting flashcard games...' . PHP_EOL;
\App\Models\FlashcardGame::query()->delete();
echo 'Deleting lessons...' . PHP_EOL;
\App\Models\Lesson::query()->delete();
echo 'All data deleted successfully!' . PHP_EOL;
"

if [ $? -ne 0 ]; then
    echo "❌ Error: Failed to delete existing data"
    exit 1
fi

echo "✅ All existing data deleted"
echo ""

echo "📥 Importing lessons and vocabulary from CSV files..."

# Run the import command
php artisan talma:import-lessons

if [ $? -ne 0 ]; then
    echo "❌ Error: Failed to import lessons"
    exit 1
fi

echo ""
echo "🎉 Reset completed successfully!"
echo ""

# Show final summary
php artisan tinker --execute="
echo '📊 Final Summary:' . PHP_EOL;
echo 'Total lessons: ' . \App\Models\Lesson::count() . PHP_EOL;
echo 'Total vocabulary: ' . \App\Models\Vocabulary::count() . PHP_EOL;
echo 'Vocabulary with Hebrew: ' . \App\Models\Vocabulary::whereNotNull('hebrew_translation')->count() . PHP_EOL;
echo 'Vocabulary with Arabic: ' . \App\Models\Vocabulary::whereNotNull('arabic_translation')->count() . PHP_EOL;
echo PHP_EOL . 'Lessons created:' . PHP_EOL;
\App\Models\Lesson::orderBy('id')->get(['id', 'title', 'session_number', 'grade_level'])->each(function(\$l) {
    echo 'ID: ' . \$l->id . ' | Session: ' . \$l->session_number . ' | Grade: ' . \$l->grade_level . ' | Title: ' . \$l->title . PHP_EOL;
});
"

echo ""
echo "✅ Production reset completed successfully!"
echo "   All lessons and vocabulary have been re-imported with Hebrew and Arabic translations."
