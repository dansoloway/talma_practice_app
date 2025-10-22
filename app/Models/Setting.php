<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        // Try to decode as JSON
        $decoded = json_decode($setting->value, true);
        return $decoded ?? $setting->value;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        $valueString = is_array($value) || is_object($value)
            ? json_encode($value)
            : $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $valueString]
        );
    }
}

