<?php
/**
 * Talks to Paystack's API from the server. Used for order payments (OrderController) and Packe Pro (ProController).
 * The secret key never leaves the server; the browser only ever sees the public key.
 */
class PaystackGateway
{
    /** Ask Paystack whether a payment really happened. Returns Paystack's "data" object, or null if it can't be confirmed. */
    public static function verify(string $reference): ?array
    {
        if (PAYSTACK_SECRET_KEY === '') {
            log_error('PAYSTACK_SECRET_KEY is not set.');
            return null;
        }
        $url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);
        $auth = 'Authorization: Bearer ' . PAYSTACK_SECRET_KEY;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [$auth],
                CURLOPT_TIMEOUT        => 20,
            ]);
            $body = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
        } else {
            // Some XAMPP setups load PHP without the curl extension; PHP's own HTTPS streams work too
            $body = @file_get_contents($url, false, stream_context_create([
                'http' => ['method' => 'GET', 'header' => $auth, 'timeout' => 20, 'ignore_errors' => true],
            ]));
            $err = $body === false ? (error_get_last()['message'] ?? 'request failed') : '';
        }
        if ($body === false) {
            log_error('Paystack verify failed: ' . $err);
            return null;
        }
        $json = json_decode($body, true);
        return ($json['status'] ?? false) ? ($json['data'] ?? null) : null;
    }

    /**
     * Checks a verified payment against what we expected to be paid.
     * Returns null when it matches, otherwise a message for the customer.
     */
    public static function mismatch(?array $data, string $reference, float $expectedAmount): ?string
    {
        if (!$data) return 'We could not confirm your payment with Paystack. You have not been charged twice; please try again.';
        if (($data['status'] ?? '') !== 'success') return 'Paystack says this payment was not successful.';
        $expectedPesewas = (int) round($expectedAmount * 100);
        if ((int) ($data['amount'] ?? 0) !== $expectedPesewas || strtoupper($data['currency'] ?? '') !== 'GHS') {
            log_error("Amount mismatch for $reference: paid " . ($data['amount'] ?? '?') . ", expected $expectedPesewas");
            return 'The amount paid does not match. Please contact us.';
        }
        return null;
    }
}
