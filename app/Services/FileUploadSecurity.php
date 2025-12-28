<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadSecurity
{
    /**
     * Sanitize filename to prevent directory traversal and other attacks
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove any non-alphanumeric characters except dots, dashes, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Remove leading dots to prevent hidden files
        $filename = ltrim($filename, '.');
        
        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'file_' . time();
        }
        
        // Limit filename length
        $filename = Str::limit($filename, 255, '');
        
        return $filename;
    }

    /**
     * Validate file content by checking MIME type matches extension
     */
    public static function validateFileContent(UploadedFile $file, array $allowedMimes): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        // Check if MIME type is in allowed list
        if (!in_array($mimeType, $allowedMimes)) {
            return false;
        }
        
        // Additional validation: check file signature for images
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            return self::validateImageSignature($file);
        }
        
        return true;
    }

    /**
     * Validate image file signature to prevent fake extensions
     */
    private static function validateImageSignature(UploadedFile $file): bool
    {
        $path = $file->getRealPath();
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $path);
        finfo_close($finfo);
        
        // Check if detected MIME matches declared MIME
        return $detectedMime === $file->getMimeType();
    }

    /**
     * Generate secure filename with timestamp and random string
     */
    public static function generateSecureFilename(UploadedFile $file, string $prefix = ''): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $sanitizedExtension = preg_replace('/[^a-z0-9]/', '', $extension);
        
        // Use sanitized original name or generate one
        $originalName = self::sanitizeFilename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        
        $timestamp = time();
        $random = Str::random(8);
        
        $filename = $prefix 
            ? "{$prefix}_{$timestamp}_{$random}.{$sanitizedExtension}"
            : "{$originalName}_{$timestamp}_{$random}.{$sanitizedExtension}";
        
        return $filename;
    }
}

