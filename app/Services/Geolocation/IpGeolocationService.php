<?php

namespace App\Services\Geolocation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class IpGeolocationService
{
    /**
     * Get country code from IP address.
     * Uses ip-api.com free tier (no API key required, 45 requests/minute limit).
     * 
     * @param string $ipAddress
     * @return string|null Two-letter country code (e.g., 'US', 'CA') or null on failure
     */
    public function getCountryFromIp(string $ipAddress): ?string
    {
        $location = $this->getLocationFromIp($ipAddress);
        return $location['country'] ?? null;
    }

    /**
     * Get full location data (country, city, region) from IP address.
     * Uses ip-api.com free tier (no API key required, 45 requests/minute limit).
     * 
     * @param string $ipAddress
     * @return array{country: string|null, city: string|null, region: string|null}
     */
    public function getLocationFromIp(string $ipAddress): array
    {
        // Skip local/private IPs
        if ($this->isPrivateIp($ipAddress)) {
            return ['country' => null, 'city' => null, 'region' => null];
        }

        // Cache results for 24 hours to avoid hitting rate limits
        $cacheKey = "ip_location_{$ipAddress}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($ipAddress) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'countryCode,city,regionName,status',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['status']) && $data['status'] === 'success') {
                        return [
                            'country' => $data['countryCode'] ?? null,
                            'city' => $data['city'] ?? null,
                            'region' => $data['regionName'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to get geolocation for IP {$ipAddress}: " . $e->getMessage());
            }

            return ['country' => null, 'city' => null, 'region' => null];
        });
    }

    /**
     * Check if IP address is private/local.
     */
    private function isPrivateIp(string $ipAddress): bool
    {
        // IPv4 private ranges
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // IPv6 localhost
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ipAddress === '::1' || str_starts_with($ipAddress, 'fe80:');
        }

        return false;
    }
}

