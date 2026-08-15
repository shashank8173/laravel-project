<?php

namespace App\Services;

use App\Models\Company;

class AttendanceGeofence
{
    /**
     * @return array{ok:bool,message:?string,distance:?float,radius:?float}
     */
    public function validate(?float $lat, ?float $lng): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => true, 'message' => null, 'distance' => null, 'radius' => null];
        }

        if ($lat === null || $lng === null) {
            return ['ok' => true, 'message' => null, 'distance' => null, 'radius' => null];
        }

        $office = $this->officeCoordinates();
        if ($office === null) {
            // No company coords configured — do not block existing punch behaviour.
            return ['ok' => true, 'message' => null, 'distance' => null, 'radius' => null];
        }

        $radius = $this->radiusMeters();
        $distance = $this->haversineMeters($lat, $lng, $office['lat'], $office['lng']);

        if ($distance > $radius) {
            return [
                'ok' => false,
                'message' => sprintf(
                    'You are %.0f m from the office. Punch is allowed within %d m.',
                    $distance,
                    $radius
                ),
                'distance' => $distance,
                'radius' => (float) $radius,
            ];
        }

        return ['ok' => true, 'message' => null, 'distance' => $distance, 'radius' => (float) $radius];
    }

    public function isEnabled(): bool
    {
        $stored = \App\Models\EmailConfiguration::getValue('GEOFENCE_ENABLED');
        if ($stored !== null && $stored !== '') {
            return in_array(strtolower(trim($stored)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) config('hrm.geofence_enabled', true);
    }

    public function radiusMeters(): int
    {
        $stored = \App\Models\EmailConfiguration::getValue('GEOFENCE_RADIUS_METERS');
        if ($stored !== null && $stored !== '' && is_numeric($stored)) {
            return max(50, (int) $stored);
        }

        return max(50, (int) config('hrm.geofence_radius_meters', 500));
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    public function officeCoordinates(): ?array
    {
        $company = Company::query()->orderBy('id')->first();
        if (! $company) {
            return null;
        }

        $fromLatField = $this->parseLatLngPair((string) ($company->latitude ?? ''));
        if ($fromLatField) {
            return $fromLatField;
        }

        $latOnly = $this->parseFloat((string) ($company->latitude ?? ''));
        $fromMap = $this->extractCoordsFromMapHtml((string) ($company->longitude ?? ''));

        if ($latOnly !== null && $fromMap) {
            // Prefer map pair when present; otherwise pair single latitude with map lng.
            return $fromMap;
        }

        return $fromMap;
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    private function parseLatLngPair(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/', $raw, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            if ($this->validCoord($lat, $lng)) {
                return ['lat' => $lat, 'lng' => $lng];
            }
        }

        return null;
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    private function extractCoordsFromMapHtml(string $html): ?array
    {
        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $patterns = [
            '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
            '/[?&]q=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
            '/center=(-?\d+(?:\.\d+)?)%2C(-?\d+(?:\.\d+)?)/i',
            '/center=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
                if ($this->validCoord($lat, $lng)) {
                    return ['lat' => $lat, 'lng' => $lng];
                }
            }
        }

        return null;
    }

    private function parseFloat(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    private function validCoord(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
            && ! ($lat == 0.0 && $lng == 0.0);
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
