<?php
/**
 * Controller: the 3D designer. Checks a design, works out its price on the server, and saves it.
 * The browser shows the same estimate (js/designer.js), but this is the price customers actually pay.
 * The templates (sizes, materials, prices) live in DesignTypes.
 * Owner: Josephine Allan
 */
require_once __DIR__ . '/../classes/CustomizationClass.php';
require_once __DIR__ . '/../classes/ProductClass.php';
require_once __DIR__ . '/Validate.php';
require_once __DIR__ . '/DesignTypes.php';

class CustomizationController
{
    private CustomizationClass $model;

    public function __construct()
    {
        $this->model = new CustomizationClass();
    }

    /**
     * $in: the designer form fields. $logoFile: the saved logo file name or null.
     * Returns ['success' => true, 'customization_id', 'product_id', 'quantity', 'unit_price', 'noun'] or an error
     * (errors may name the 'field' they belong to).
     */
    public function saveDesign(array $in, ?string $logoFile, string $cartKey, ?int $customerId): array
    {
        $product = (new ProductClass())->getProductById((int) Validate::positiveInt($in['product_id'] ?? null));
        $type = $product['design_type'] ?? null;
        $t = DesignTypes::get($type);
        if (!$product || !$t) return Validate::fail('That product cannot be designed.');

        $dims = ['length' => 0.0, 'width' => 0.0, 'height' => 0.0];
        foreach ($t['fields'] as $k => [$label, $lo, $hi]) {
            $v = filter_var($in[$k] ?? '', FILTER_VALIDATE_FLOAT);
            $unit = $type === 'catalog' && $k === 'height' ? '' : ' cm';
            if ($v === false || $v < $lo || $v > $hi) {
                return ['success' => false, 'field' => 'length', 'error' => "$label must be between $lo and $hi$unit."];
            }
            $dims[$k] = round($v, 1);
        }
        if ($type === 'catalog' && ((int) $dims['height'] != $dims['height'] || (int) $dims['height'] % 4 !== 0)) {
            return ['success' => false, 'field' => 'length', 'error' => 'Catalogues are printed in sets of 4 pages (8, 12, 16 …).'];
        }
        [$L, $W, $H] = DesignTypes::normalise($type, $dims['length'], $dims['width'], $dims['height']);

        $material = $in['material'] ?? '';
        if (!isset($t['materials'][$material])) return Validate::fail('Choose a material.');
        $ink = (string) ($in['print_colour'] ?? '');
        if ($ink !== '' && !preg_match(Validate::HEX, $ink)) return Validate::fail('Choose a valid print colour.');
        $text = Validate::clean($in['brand_text'] ?? '');
        if (mb_strlen($text) > 28) return ['success' => false, 'field' => 'brand_text', 'error' => 'Text must be 28 characters or fewer.'];
        $qty = (int) ($in['quantity'] ?? 0);
        if (!in_array($qty, $t['qtys'], true)) return Validate::fail('Choose a quantity.');

        $price = DesignTypes::unitPrice($type, $L, $W, $H, $material, $ink !== '', $logoFile !== null || $text !== '', $qty);

        $id = $this->model->addCustomization([
            'customer_id'  => $customerId,
            'cart_key'     => $cartKey,
            'product_id'   => (int) $product['product_id'],
            'length_cm'    => $L,
            'width_cm'     => $W,
            'height_cm'    => $H,
            'material'     => $material,
            'print_colour' => $ink === '' ? null : strtolower($ink),
            'logo_file'    => $logoFile,
            'brand_text'   => $text === '' ? null : $text,
            'quantity'     => $qty,
            'unit_price'   => $price,
        ]);
        return ['success' => true, 'customization_id' => $id, 'product_id' => (int) $product['product_id'],
                'quantity' => $qty, 'unit_price' => $price, 'noun' => $t['noun'][1]];
    }

    public function getDesignsByCustomer(int $customerId): array
    {
        return $this->model->getCustomizationsByCustomer($customerId);
    }
}
