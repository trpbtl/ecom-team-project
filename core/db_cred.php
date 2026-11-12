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

// Delivery fees in GH₵ (set by the team)
define('DELIVERY_FEE_STANDARD', (float) cfg('DELIVERY_FEE_STANDARD', '20'));
define('DELIVERY_FEE_EXPRESS',  (float) cfg('DELIVERY_FEE_EXPRESS', '50'));

// Sign in with Google (free): Google Cloud Console > APIs & Services > Credentials > OAuth client ID (Web application)
define('GOOGLE_CLIENT_ID', cfg('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', cfg('GOOGLE_CLIENT_SECRET'));

// Optional: an email shown on the privacy page for questions about personal data
define('SUPPORT_EMAIL', cfg('SUPPORT_EMAIL'));

// Optional: the site's public address (e.g. https://packe.onrender.com). Worked out automatically when empty.
define('PUBLIC_URL', rtrim(cfg('PUBLIC_URL'), '/'));
