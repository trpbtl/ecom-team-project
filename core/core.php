<?php
/**
 * Included once at the top of every view and action.
 * Session, configuration, error logging and shared helpers. No SQL, no HTML.
 */
require_once __DIR__ . '/db_class.php';

date_default_timezone_set('Africa/Accra');

/* ---------- Error logging to error/error.log ---------- */
define('APP_ROOT', dirname(__DIR__));
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('error_log', APP_ROOT . '/error/error.log');

function log_error(string $message): void
{
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message);
}

/* ---------- Base URL, so links work in any folder (htdocs/packe, htdocs/ecom-team-project, or the site root) ---------- */
(function () {
    $base = cfg('BASE_URL');
    if ($base === '') {
        $app = str_replace('\\', '/', (string) realpath(APP_ROOT));
        $doc = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $base = ($doc !== '' && stripos($app, $doc) === 0) ? substr($app, strlen($doc)) : '';
    }
    define('BASE_URL', rtrim($base, '/'));
})();

/* ---------- Session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    ]);
    session_start();
}

/* ---------- Small helpers ---------- */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Full address of a page, e.g. https://packe.onrender.com/actions/google_callback.php (Google needs the whole address). */
function absolute_url(string $path = ''): string
{
    if (PUBLIC_URL !== '') return PUBLIC_URL . '/' . ltrim($path, '/');
    $https = !empty($_SERVER['HTTPS']) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^A-Za-z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');   // only host characters
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

/** Log a customer in: fresh session id (stops session fixation), then their details in the session. */
function start_customer_session(array $c): void
{
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int) $c['customer_id'];
    $_SESSION['customer_name'] = $c['full_name'];
    $_SESSION['customer_email'] = $c['email'];
    $_SESSION['user_role'] = (int) $c['user_role'];
}

/** After logging in: back to the page that asked for it (if on this site), or the right home page for the role. */
function redirect_after_login(): void
{
    $next = $_SESSION['after_login'] ?? '';
    unset($_SESSION['after_login']);
    if ($next !== '' && str_starts_with($next, BASE_URL . '/') && !str_contains($next, '://')) {
        header('Location: ' . $next);
        exit;
    }
    if (is_admin()) redirect('views/admin/dashboard.php');
    if (is_delivery()) redirect('views/delivery/my_deliveries.php');
    redirect('index.php');
}

/** URL of a script or stylesheet with a version stamp, so browsers fetch the new file after each change. */
function asset_url(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** The import map and module script for pages with 3D (Three.js from the CDN, plus our shared packaging models). */
function three_scripts(string $script): string
{
    $map = ['imports' => [
        'three'         => 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js',
        'three/addons/' => 'https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/',
        'packe3d'       => asset_url('js/packaging3d.js'),
    ]];
    return '<script type="importmap">' . json_encode($map, JSON_UNESCAPED_SLASHES) . '</script>'
         . '<script type="module" src="' . e(asset_url($script)) . '"></script>';
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/** Escape for HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return 'GH₵' . number_format((float) $amount, 2);
}

/**
 * Product pictures: "catalog/<name>.svg" are Packe's own illustrations shipped with the code;
 * anything else is an image an admin uploaded to images/products/.
 */
function product_image_url(?string $file): string
{
    if (!$file) {
        return url('images/placeholder.svg');
    }
    if (preg_match('#^catalog/[a-z0-9-]+\.svg$#', $file)) {
        return asset_url('images/' . $file);   // versioned, so updated pictures show at once
    }
    return url('images/products/' . rawurlencode(basename($file)));
}

/* ---------- Flash messages ($_SESSION['error'] / $_SESSION['success']) ---------- */
function flash(string $type, string $message): void
{
    $_SESSION[$type] = $message;
}

function take_flash(string $type): ?string
{
    $msg = $_SESSION[$type] ?? null;
    unset($_SESSION[$type]);
    return $msg;
}

/** Keep what the user typed so a form can be refilled after a validation error. */
function remember_input(array $fields): void
{
    $_SESSION['old'] = array_intersect_key($_POST, array_flip($fields));
}

function old(string $field, string $default = ''): string
{
    return e($_SESSION['old'][$field] ?? $default);
}

function forget_input(): void
{
    unset($_SESSION['old']);
}

/* ---------- CSRF protection for every POST form ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** For actions: only allow POST with a valid CSRF token. */
function require_post(string $backTo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        redirect($backTo);
    }
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        if (wants_json()) {
            json_out(['success' => false, 'error' => 'Your session expired. Please refresh the page and try again.'], 419);
        }
        flash('error', 'Your session expired. Please try again.');
        redirect($backTo);
    }
}

/* ---------- Background requests (js/cart.js sends forms without reloading the page) ---------- */
function wants_json(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data);
    exit;
}

/* ---------- Access control ---------- */
const ROLE_ADMIN = 1;
const ROLE_CUSTOMER = 2;
const ROLE_DELIVERY = 3;

function is_logged_in(): bool
{
    return isset($_SESSION['customer_id']);
}

function is_admin(): bool
{
    return is_logged_in() && (int) ($_SESSION['user_role'] ?? 0) === ROLE_ADMIN;
}

function is_delivery(): bool
{
    return is_logged_in() && (int) ($_SESSION['user_role'] ?? 0) === ROLE_DELIVERY;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('error', 'Please log in to continue.');
        redirect('views/login.php');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('error', 'That page is for administrators only.');
        redirect('index.php');
    }
}

function require_delivery(): void
{
    if (!is_delivery() && !is_admin()) {
        flash('error', 'That page is for delivery staff only.');
        redirect('index.php');
    }
}

/* ---------- Cart key: a random token in a cookie, so guests can shop before logging in ---------- */
function get_cart_key(): string
{
    $key = $_COOKIE['packe_cart'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
        $key = bin2hex(random_bytes(16));
        setcookie('packe_cart', $key, [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['packe_cart'] = $key;
    }
    return $key;
}

/* ---------- Uploads ---------- */
/**
 * Validate and save an uploaded image. Returns the new file name, null if no file was sent,
 * or throws InvalidArgumentException with a message for the user.
 */
function save_uploaded_image(string $field, string $folder, int $maxBytes = 2097152, bool $allowSvg = false): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The image could not be uploaded. Please try again.');
    }
    if ($f['size'] > $maxBytes) {
        throw new InvalidArgumentException('The image is too large. The limit is ' . round($maxBytes / 1048576) . ' MB.');
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if ($allowSvg) {
        $types['image/svg+xml'] = 'svg';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);   // check the real file, not the browser's claim
    if (!isset($types[$mime])) {
        throw new InvalidArgumentException('Please upload a JPG, PNG, GIF or WebP image' . ($allowSvg ? ' (or SVG)' : '') . '.');
    }
    $dir = APP_ROOT . '/images/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = bin2hex(random_bytes(10)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        throw new InvalidArgumentException('The image could not be saved. Please try again.');
    }
    return $name;
}
