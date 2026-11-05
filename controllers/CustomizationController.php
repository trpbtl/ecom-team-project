<?php
/**
 * Controller: the 3D box designer. Checks a design, works out its price on the server, and saves it.
 * The browser shows the same estimate (js/designer.js), but this is the price customers actually pay.
 * Owner: Josephine Allan
 */
require_once __DIR__ . '/../classes/CustomizationClass.php';
require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/Validate.php';

class CustomizationController
{
    public const LIMITS = ['length' => [10, 60], 'width' => [8, 50], 'height' => [3, 30]];
    public const TIERS  = [50 => 1.00, 100 => 0.88, 250 => 0.76, 500 => 0.66, 1000 => 0.56];   // quantity => price factor

    private CustomizationClass $model;

    public function __construct()
    {
        $this->model = new CustomizationClass();
    }

    /** Area of the flat sheet (cm²), using the same mailer box layout the designer draws. */
    public static function boardArea(float $L, float $W, float $H): float
    {
        $lidD = $W - 0.4;
        $tuckH = $H * 0.8;
        $earW = $H * 0.85;
        $earD = $lidD * 0.8;
        return $L * $W + 2 * $L * $H + 2 * $H * $W + $L * $lidD + ($L - 0.8) * $tuckH + 2 * $earW * $earD;
    }

    public static function unitPrice(float $base, float $L, float $W, float $H, string $material,
                                     bool $hasInk, bool $hasBranding, int $qty): float
    {
        $u = $base + self::boardArea($L, $W, $H) * 0.0011 * ($material === 'white' ? 1.15 : 1.0);
        if ($hasInk) $u += 0.90;
        if ($hasBranding) $u += 0.60;
        return round($u * self::TIERS[$qty], 2);
    }

    /**
     * $in: the designer form fields. $logoFile: the saved logo file name or null.
     * Returns ['success' => true, 'customization_id' => .., 'product_id' => .., 'quantity' => ..] or an error.
     */
    public function saveDesign(array $in, ?string $logoFile, string $cartKey, ?int $customerId): array
    {
        $product = (new ProductClass())->getProductById((int) Validate::positiveInt($in['product_id'] ?? null));
        if (!$product || !$product['is_customizable']) return Validate::fail('That product cannot be designed.');

        $dims = [];
        foreach (self::LIMITS as $k => [$lo, $hi]) {
            $v = filter_var($in[$k] ?? '', FILTER_VALIDATE_FLOAT);
            if ($v === false || $v < $lo || $v > $hi) {
                return Validate::fail(ucfirst($k) . " must be between $lo and $hi cm.");
            }
            $dims[$k] = round($v, 1);
        }
        $material = $in['material'] ?? '';
        if (!in_array($material, ['kraft', 'white'], true)) return Validate::fail('Choose kraft or white board.');
        $ink = (string) ($in['print_colour'] ?? '');
        if ($ink !== '' && !preg_match(Validate::HEX, $ink)) return Validate::fail('Choose a valid print colour.');
        $text = Validate::clean($in['brand_text'] ?? '');
        if (mb_strlen($text) > 28) return Validate::fail('Text on the box must be 28 characters or fewer.');
        $qty = (int) ($in['quantity'] ?? 0);
        if (!isset(self::TIERS[$qty])) return Validate::fail('Choose a quantity.');

        $price = self::unitPrice((float) $product['base_price'], $dims['length'], $dims['width'], $dims['height'],
                                 $material, $ink !== '', $logoFile !== null || $text !== '', $qty);

        $id = $this->model->addCustomization([
            'customer_id'  => $customerId,
            'cart_key'     => $cartKey,
            'product_id'   => (int) $product['product_id'],
            'length_cm'    => $dims['length'],
            'width_cm'     => $dims['width'],
            'height_cm'    => $dims['height'],
            'material'     => $material,
            'print_colour' => $ink === '' ? null : strtolower($ink),
            'logo_file'    => $logoFile,
            'brand_text'   => $text === '' ? null : $text,
            'quantity'     => $qty,
            'unit_price'   => $price,
        ]);
        return ['success' => true, 'customization_id' => $id, 'product_id' => (int) $product['product_id'], 'quantity' => $qty];
    }

    public function getDesignsByCustomer(int $customerId): array
    {
        return $this->model->getCustomizationsByCustomer($customerId);
    }
}
