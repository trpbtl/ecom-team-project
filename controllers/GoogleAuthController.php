<?php
/**
 * Controller: "Continue with Google" (OAuth 2.0 / OpenID Connect, authorization code flow).
 *
 * 1. start(): send the customer to Google with a random `state` (stops forged callbacks) and `nonce`.
 * 2. Google sends them back to actions/google_callback.php with a one-time `code`.
 * 3. finish(): our server swaps the code for an ID token at Google's token endpoint (using the client secret),
 *    then checks the token is for our app, from Google, not expired, and carries our nonce.
 * Because the token comes straight from Google over HTTPS, its signature does not need to be checked again
 * (OpenID Connect Core 3.1.3.7). No SQL or HTML here: CustomerController decides who the person is.
 * Owner: Hagen Dickson (accounts)
 */
require_once __DIR__ . '/../core/core.php';

class GoogleAuthController
{
    private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const ISSUERS   = ['https://accounts.google.com', 'accounts.google.com'];

    /** The button only shows when both keys are set. */
    public static function isEnabled(): bool
    {
        return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
    }

    /** Must match an "Authorized redirect URI" in Google Cloud Console exactly. */
    public static function redirectUri(): string
    {
        return absolute_url('actions/google_callback.php');
    }

    /** Remember state + nonce in the session and return Google's sign-in address. */
    public function start(): string
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['google_oauth'] = ['state' => $state, 'nonce' => $nonce, 'time' => time()];
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'nonce'         => $nonce,
            'prompt'        => 'select_account',   // let people pick which Google account
        ]);
    }

    /**
     * Check the callback and get the person's Google identity.
     * Returns ['success' => true, 'who' => ['provider', 'sub', 'email', 'email_verified', 'name']] or an error.
     */
    public function finish(array $query): array
    {
        $saved = $_SESSION['google_oauth'] ?? null;
        unset($_SESSION['google_oauth']);   // each sign-in attempt can be used once

        if (isset($query['error'])) {
            return ['success' => false, 'error' => 'Google sign-in was cancelled.'];
        }
        if (!$saved || time() - $saved['time'] > 600
            || !hash_equals($saved['state'], (string) ($query['state'] ?? ''))) {
            return ['success' => false, 'error' => 'That Google sign-in link has expired. Please try again.'];
        }
        $code = (string) ($query['code'] ?? '');
        if ($code === '' || strlen($code) > 512) {
            return ['success' => false, 'error' => 'Google did not send a sign-in code. Please try again.'];
        }

        $token = $this->postForm(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
        ]);
        $claims = isset($token['id_token']) ? $this->jwtPayload($token['id_token']) : null;
        if (!$claims) {
            log_error('Google token exchange failed: ' . json_encode($token));
            return ['success' => false, 'error' => 'We could not confirm your Google sign-in. Please try again.'];
        }

        $aud = (array) ($claims['aud'] ?? []);
        if (!in_array($claims['iss'] ?? '', self::ISSUERS, true)
            || !in_array(GOOGLE_CLIENT_ID, $aud, true)
            || (int) ($claims['exp'] ?? 0) < time()
            || !hash_equals($saved['nonce'], (string) ($claims['nonce'] ?? ''))
            || empty($claims['sub'])) {
            log_error('Google ID token rejected: ' . json_encode(['iss' => $claims['iss'] ?? null, 'aud' => $aud]));
            return ['success' => false, 'error' => 'We could not confirm your Google sign-in. Please try again.'];
        }

        return ['success' => true, 'who' => [
            'provider'       => 'google',
            'sub'            => (string) $claims['sub'],
            'email'          => (string) ($claims['email'] ?? ''),
            'email_verified' => ($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? '') === 'true',
            'name'           => (string) ($claims['name'] ?? ''),
        ]];
    }

    /** The middle part of a JWT, decoded. */
    private function jwtPayload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        $data = $json === false ? null : json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    /** HTTPS POST of a form, JSON answer. Uses curl when PHP has it, otherwise PHP's own HTTPS streams. */
    protected function postForm(string $url, array $fields): ?array   // protected so tests can stand in for Google
    {
        $body = http_build_query($fields);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT        => 20,
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
        } else {
            $res = @file_get_contents($url, false, stream_context_create(['http' => [
                'method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $body, 'timeout' => 20, 'ignore_errors' => true,
            ]]));
        }
        $json = is_string($res) ? json_decode($res, true) : null;
        return is_array($json) ? $json : null;
    }
}
