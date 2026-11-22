<?php
/**
 * Who delivers an order: Packe's own riders, or a ride-hailing delivery partner (Uber, Yango Delivery, Bolt).
 *
 * The partners don't offer a public booking API to small businesses, so Packe books the ride itself in the
 * partner's app and records the driver's details; the customer sees them on their order. Customers pay Packe's
 * usual delivery price plus a small booking fee (a revenue stream); Packe pays the partner's fare.
 */
class Couriers
{
    public const PARTNER_FEE = 5.00;   // GH₵ booking fee on partner deliveries

    public const LIST = [
        'packe' => ['name' => 'Packe Delivery', 'logo' => 'images/couriers/packe.svg', 'short' => 'Packe', 'badge' => 'P', 'color' => '#c2410c', 'text' => '#ffffff',
                    'blurb' => 'Our own riders. Packe Pro members get standard delivery free.', 'app' => null],
        'uber'  => ['name' => 'Uber',           'logo' => 'images/couriers/uber.png',  'short' => 'Uber',  'badge' => 'U', 'color' => '#000000', 'text' => '#ffffff',
                    'blurb' => 'Booked by Packe with Uber.', 'app' => 'https://www.uber.com/gh/en/'],
        'yango' => ['name' => 'Yango Delivery', 'logo' => 'images/couriers/yango.png', 'short' => 'Yango', 'badge' => 'Y', 'color' => '#e8382e', 'text' => '#ffffff',
                    'blurb' => 'Booked by Packe with Yango Delivery.', 'app' => 'https://yango.com/en_gh/'],
        'bolt'  => ['name' => 'Bolt',           'logo' => 'images/couriers/bolt.png',  'short' => 'Bolt',  'badge' => 'B', 'color' => '#2f9e66', 'text' => '#ffffff',
                    'blurb' => 'Booked by Packe with Bolt.', 'app' => 'https://bolt.eu/en-gh/'],
    ];

    public static function valid(?string $key): string
    {
        return isset(self::LIST[$key ?? '']) ? $key : 'packe';
    }

    public static function isPartner(string $key): bool
    {
        return self::valid($key) !== 'packe';
    }

    public static function get(?string $key): array
    {
        return self::LIST[self::valid($key)];
    }

    /**
     * The delivery fee for a quote (from DeliveryPricing, after ProController::applyToQuote), speed and courier.
     * Packe Pro's free standard delivery is for Packe riders only; partners add the booking fee.
     */
    public static function fee(array $quote, string $option, string $carrier): float
    {
        if (!self::isPartner($carrier)) return (float) $quote[$option];
        $base = ($option === 'standard' && isset($quote['standard_full'])) ? $quote['standard_full'] : $quote[$option];
        return round((float) $base + self::PARTNER_FEE, 2);
    }
}
