<?php
/**
 * Controller: delivery prices by distance from Packe (Ashesi University, Berekuso).
 *
 * Road distance comes from free services, in this order:
 *   1. OpenRouteService, when ORS_API_KEY is set (free account, no card)
 *   2. the public OSRM demo server (no account)
 *   3. straight-line distance × 1.3, so checkout never breaks if both are down
 * Standard = DELIVERY_BASE + DELIVERY_PER_KM × km; express = standard × DELIVERY_EXPRESS_FACTOR.
 * The browser only shows these prices: checkout asks this class again, so they can't be changed client-side.
 * Owner: Eyram Awoye (orders and delivery)
 */
require_once __DIR__ . '/../core/core.php';

class DeliveryPricing
{
    public const ORIGIN_LABEL = 'Ashesi University, Berekuso';
    private const ROAD_FACTOR = 1.3;   // straight line to road distance, a common rule of thumb
    private const CACHE_SIZE  = 20;    // recent quotes kept per visitor
    private const MAX_LOOKUPS = 40;    // route look-ups per visitor per 10 minutes (protects the free services)

    /** Is this point inside Ghana (roughly)? */
    public static function inGhana(float $lat, float $lng): bool
    {
        return $lat >= 4.5 && $lat <= 11.5 && $lng >= -3.5 && $lng <= 1.5;
    }

    /** The two prices for a road distance. */
    public static function fees(float $km): array
    {
        $standard = round(DELIVERY_BASE + DELIVERY_PER_KM * $km, 2);
        return ['standard' => $standard, 'express' => round($standard * DELIVERY_EXPRESS_FACTOR, 2)];
    }

    /**
     * Distance and prices from Packe to a point.
     * Returns ['success' => true, 'km', 'minutes'|null, 'method' => 'road'|'estimate', 'standard', 'express', 'line' => [[lat, lng], …]]
     * or ['success' => false, 'error' => …].
     */
    public function quote($lat, $lng): array
    {
        $lat = filter_var($lat, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($lng, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || !self::inGhana($lat, $lng)) {
            return ['success' => false, 'error' => 'Choose a delivery spot in Ghana on the map.'];
        }
        // About 11 m precision is plenty, and lets nearby repeats reuse the answer
        $key = sprintf('%.4f,%.4f', $lat, $lng);
        $cache = $_SESSION['delivery_quotes'] ?? [];
        if (isset($cache[$key])) return $cache[$key];

        $route = $this->route($lat, $lng);
        if ($route === null) {
            $route = ['km' => $this->straightKm($lat, $lng) * self::ROAD_FACTOR, 'minutes' => null, 'line' => [], 'method' => 'estimate'];
        }
        $km = round($route['km'], 1);
        if ($km > DELIVERY_MAX_KM) {
            $result = ['success' => false, 'km' => $km,
                       'error' => 'We deliver up to ' . (int) DELIVERY_MAX_KM . ' km from Ashesi. Please contact us for a quote.'];
        } else {
            $result = ['success' => true, 'km' => $km, 'minutes' => $route['minutes'], 'method' => $route['method'],
                       'line' => $route['line']] + self::fees($km);
        }
        $cache[$key] = $result;
        $_SESSION['delivery_quotes'] = array_slice($cache, -self::CACHE_SIZE, null, true);
        return $result;
    }

    /** Road route from Packe, or null if no routing service answered (or this visitor asked too often). */
    private function route(float $lat, float $lng): ?array
    {
        $window = $_SESSION['delivery_lookups'] ?? ['since' => time(), 'count' => 0];
        if (time() - $window['since'] > 600) $window = ['since' => time(), 'count' => 0];
        $window['count']++;
        $_SESSION['delivery_lookups'] = $window;
        if ($window['count'] > self::MAX_LOOKUPS) return null;

        $from = DELIVERY_ORIGIN_LNG . ',' . DELIVERY_ORIGIN_LAT;   // these services want longitude first
        $to = $lng . ',' . $lat;
        if (ORS_API_KEY !== '') {
            $json = $this->getJson('https://api.openrouteservice.org/v2/directions/driving-car?'
                . http_build_query(['api_key' => ORS_API_KEY, 'start' => $from, 'end' => $to]));
            $f = $json['features'][0] ?? null;
            if ($f && isset($f['properties']['summary']['distance'])) {
                return $this->routeResult($f['properties']['summary']['distance'], $f['properties']['summary']['duration'] ?? null,
                                          $f['geometry']['coordinates'] ?? []);
            }
        }
        $json = $this->getJson("https://router.project-osrm.org/route/v1/driving/$from;$to?overview=simplified&geometries=geojson");
        $r = $json['routes'][0] ?? null;
        if ($r && isset($r['distance'])) {
            return $this->routeResult($r['distance'], $r['duration'] ?? null, $r['geometry']['coordinates'] ?? []);
        }
        return null;
    }

    private function routeResult(float $metres, ?float $seconds, array $coords): array
    {
        // At most ~200 points for the map line, as [lat, lng]
        $step = max(1, (int) ceil(count($coords) / 200));
        $line = [];
        foreach ($coords as $i => $c) {
            if ($i % $step === 0 || $i === count($coords) - 1) $line[] = [round($c[1], 5), round($c[0], 5)];
        }
        return ['km' => $metres / 1000, 'minutes' => $seconds === null ? null : (int) round($seconds / 60),
                'line' => $line, 'method' => 'road'];
    }

    /** Great-circle (straight-line) distance from Packe in km. */
    private function straightKm(float $lat, float $lng): float
    {
        $r = fn($d) => deg2rad($d);
        $dLat = $r($lat - DELIVERY_ORIGIN_LAT);
        $dLng = $r($lng - DELIVERY_ORIGIN_LNG);
        $h = sin($dLat / 2) ** 2 + cos($r(DELIVERY_ORIGIN_LAT)) * cos($r($lat)) * sin($dLng / 2) ** 2;
        return 2 * 6371 * asin(sqrt($h));
    }

    /** HTTPS GET returning decoded JSON (curl if PHP has it, otherwise PHP's own streams). */
    private function getJson(string $url): ?array
    {
        $agent = 'Packe/1.0 (student e-commerce project)';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => $agent]);
            $body = curl_exec($ch);
            curl_close($ch);
        } else {
            $body = @file_get_contents($url, false, stream_context_create(['http' => [
                'timeout' => 8, 'header' => "User-Agent: $agent", 'ignore_errors' => true,
            ]]));
        }
        $json = is_string($body) ? json_decode($body, true) : null;
        return is_array($json) ? $json : null;
    }
}
