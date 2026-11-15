<?php
/**
 * Configuration. No real secrets live in this file.
 * - Locally (XAMPP): the defaults below work, or copy local_cred.example.php to local_cred.php.
 * - On Render: set these as environment variables in the dashboard.
 */
if (file_exists(__DIR__ . '/local_cred.php')) {
    require_once __DIR__ . '/local_cred.php';
}

function cfg(string $name, string $default = ''): string
{
    if (defined('LOCAL_' . $name)) {
        return constant('LOCAL_' . $name);
    }
    $v = getenv($name);
    return ($v === false || $v === '') ? $default : $v;
}

define('DB_HOST', cfg('DB_HOST', 'localhost'));
define('DB_PORT', (int) cfg('DB_PORT', '3306'));
define('DB_USER', cfg('DB_USER', 'root'));
define('DB_PASS', cfg('DB_PASS', ''));
define('DB_NAME', cfg('DB_NAME', 'packe'));
define('DB_SSL',  cfg('DB_SSL', 'false') === 'true');     // Aiven needs SSL: set DB_SSL=true
define('DB_SSL_CA', cfg('DB_SSL_CA'));                     // Aiven's CA certificate (the text of ca.pem), so the connection is verified

define('PAYSTACK_PUBLIC_KEY', cfg('PAYSTACK_PUBLIC_KEY'));   // pk_test_...
define('PAYSTACK_SECRET_KEY', cfg('PAYSTACK_SECRET_KEY'));   // sk_test_...

// Delivery pricing by distance from Packe (Ashesi University, Berekuso). Fees in GH₵, set by the team.
// Standard = base + per km (road distance); express = standard × the express factor.
define('DELIVERY_BASE', (float) cfg('DELIVERY_BASE', '25'));
define('DELIVERY_PER_KM', (float) cfg('DELIVERY_PER_KM', '1.5'));
define('DELIVERY_EXPRESS_FACTOR', (float) cfg('DELIVERY_EXPRESS_FACTOR', '1.8'));
define('DELIVERY_MAX_KM', (float) cfg('DELIVERY_MAX_KM', '150'));
define('DELIVERY_ORIGIN_LAT', (float) cfg('DELIVERY_ORIGIN_LAT', '5.7598'));    // Ashesi University
define('DELIVERY_ORIGIN_LNG', (float) cfg('DELIVERY_ORIGIN_LNG', '-0.2197'));

// Optional: OpenRouteService key (free account at openrouteservice.org, no card) for road distances.
// Without it, Packe uses the free OSRM demo server, and if that is down, straight-line distance × 1.3.
define('ORS_API_KEY', cfg('ORS_API_KEY'));

// Sign in with Google (free): Google Cloud Console > APIs & Services > Credentials > OAuth client ID (Web application)
define('GOOGLE_CLIENT_ID', cfg('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', cfg('GOOGLE_CLIENT_SECRET'));

// Optional: an email shown on the privacy page for questions about personal data
define('SUPPORT_EMAIL', cfg('SUPPORT_EMAIL'));

// Optional: the site's public address (e.g. https://packe.onrender.com). Worked out automatically when empty.
define('PUBLIC_URL', rtrim(cfg('PUBLIC_URL'), '/'));
