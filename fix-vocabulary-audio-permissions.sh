#!/bin/bash
# Fix vocabulary-audio directory ownership and permissions on production

echo "Fix vocabulary-audio directory ownership to match tts directory:"
echo ""
echo "chown -R pefnmxyext:www-data /home/1428986.cloudwaysapps.com/pefnmxyext/public_html/storage/app/public/vocabulary-audio"
echo ""
echo "chmod 755 /home/1428986.cloudwaysapps.com/pefnmxyext/public_html/storage/app/public/vocabulary-audio"
echo ""
echo "Verify the fix:"
echo "ls -ld /home/1428986.cloudwaysapps.com/pefnmxyext/public_html/storage/app/public/vocabulary-audio"
echo ""
echo "Should show: drwxr-xr-x pefnmxyext www-data (or similar)"

