<?php
/**
 * Controller: builds believable demo data for presentations (admin Dashboard → Demo data).
 *
 * Ten Ghanaian businesses place about 40 orders over the last 30 days: small label runs to large box orders,
 * at every stage (paid, processing, ready, out for delivery, delivered, cancelled), delivered by Packe riders
 * or by Uber, Yango and Bolt, with three Packe Pro members. Prices come from the real pricing rules
 * (DesignTypes, DeliveryPricing, Couriers, ProController), so the numbers match what the site would charge.
 * Everything is tagged by DemoDataClass and can be removed again.
 */
require_once __DIR__ . '/../classes/DemoDataClass.php';
require_once __DIR__ . '/DesignTypes.php';
require_once __DIR__ . '/DeliveryPricing.php';
require_once __DIR__ . '/Couriers.php';
require_once __DIR__ . '/ProController.php';
require_once __DIR__ . '/Validate.php';

class DemoDataController
{
    private const ORDER_COUNT = 42;

    /** Business, contact, area, latitude, longitude, Packe Pro member?, products they buy */
    private const BUSINESSES = [
        ["Mama Esi's Kitchen",  'Esi Quaye',        'Osu',        5.5560, -0.1823, true,  ['Kraft Food Box', 'Branded Thank-You Card', 'Circular Product Sticker']],
        ['Glow by Efua',        'Efua Mensah',      'East Legon', 5.6350, -0.1570, true,  ['Clear Window Pouch', 'Rectangular Address Label', 'Tamper Evident Seal']],
        ['Kente Threads',       'Kwesi Adjei',      'Achimota',   5.6150, -0.2300, true,  ['Collapsible Apparel Box', 'Branded Thank-You Card']],
        ['Accra Bakehouse',     'Nana Ama Boadu',   'Labone',     5.5650, -0.1700, false, ['Kraft Food Box', 'Circular Product Sticker']],
        ['Shea Naturals GH',    'Abla Dzidzienyo',  'Tema',       5.6698, -0.0166, false, ['Clear Window Pouch', 'Rectangular Address Label']],
        ['TechHub Gadgets',     'Selorm Agbeko',    'Spintex',    5.6350, -0.1000, true,  ['Corrugated Shipping Box', 'Custom Mailer Box']],
        ['Afro Chic Boutique',  'Adwoa Sarpong',    'Madina',     5.6800, -0.1650, false, ['Custom Mailer Box', 'Branded Thank-You Card', 'Marketing Tri-fold Brochure']],
        ['Golden Grains Mills', 'Kwaku Amoah',      'Kasoa',      5.5340, -0.4200, false, ['Corrugated Shipping Box', 'Rectangular Address Label']],
        ['Sweet Moments Cakes', 'Akua Frimpong',    'Dansoman',   5.5480, -0.2640, false, ['Kraft Food Box', 'Branded Thank-You Card']],
        ['BrewHouse Coffee',    'Yaw Darko',        'Adenta',     5.7060, -0.1600, true,  ['Clear Window Pouch', 'Circular Product Sticker', 'Business Product Catalog']],
    ];
    private const PARTNER_DRIVERS = ['Emmanuel Tetteh', 'Prince Owusu', 'Isaac Nkansah', 'Samuel Ankrah', 'Daniel Quarshie', 'Michael Boadi'];
    private const COLOURS = ['#c2410c', '#1d4ed8', '#15803d', '#111827', '#be185d', '#7c3aed'];

    private DemoDataClass $model;

    public function __construct()
    {
        $this->model = new DemoDataClass();
    }

    public function status(): array
    {
        return $this->model->counts();
    }

    public function remove(): array
    {
        $this->model->removeAll();
        return ['success' => true];
    }

    public function load(): array
    {
        if ((int) $this->model->counts()['orders'] > 0) return Validate::fail('Demo data is already loaded. Remove it first to load a fresh set.');

        $products = [];
        foreach ($this->model->products() as $p) $products[$p['name']] = $p;
        $riders = $this->model->riders();
        mt_srand(2026);   // the same believable set every time

        $this->model->begin();
        try {
            // Businesses; five are Packe Pro members who joined on different days this month
            $customers = [];
            $joinDays = [27, 21, 14, 9, 3];   // days ago each Pro member joined
            $proCount = 0;
            foreach (self::BUSINESSES as $i => [$business, $contact, $area, $lat, $lng, $pro, $buys]) {
                $c = [
                    'business' => $business, 'contact' => $contact, 'area' => $area,
                    'email'    => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '.', $business), '.')) . DemoDataClass::EMAIL_DOMAIN,
                    'phone'    => sprintf('02490001%02d', $i + 1),   // placeholder numbers
                    'address'  => "Shop " . ($i + 3) . ", $area Main Road",
                ];
                $c['id'] = $this->model->addCustomer($c, date('Y-m-d H:i:s', strtotime('-' . (35 + 3 * $i) . ' days 10:00')));
                $proSince = null;
                if ($pro) {
                    $proSince = date('Y-m-d', strtotime('-' . $joinDays[$proCount++ % count($joinDays)] . ' days')) . sprintf(' %02d:15:00', 9 + $i % 8);
                    $this->model->addSubscription($c['id'], DemoDataClass::REF_PREFIX . 'PRO-' . ($i + 1), $proSince,
                                                  date('Y-m-d H:i:s', strtotime($proSince . ' +30 days')));
                }
                $customers[] = $c + ['lat' => $lat, 'lng' => $lng, 'pro' => $pro, 'pro_since' => $proSince, 'buys' => $buys];
            }

            $partnerTurn = 0;
            $made = 0;
            $revenue = 0.0;
            for ($n = 0; $n < self::ORDER_COUNT; $n++) {
                $c = $customers[mt_rand(0, count($customers) - 1)];
                $ago = (int) floor(29 * pow(mt_rand(0, 1000) / 1000, 1.4));   // more orders recently
                $createdAt = date('Y-m-d', strtotime("-$ago days")) . sprintf(' %02d:%02d:00', mt_rand(8, 18), mt_rand(0, 59));
                if (strtotime($createdAt) > time()) $createdAt = date('Y-m-d H:i:s', time() - mt_rand(600, 7200));

                // 1 to 3 products this business buys: mostly custom designs, sometimes ready-made
                $items = [];
                $picks = (array) array_rand(array_flip($c['buys']), min(count($c['buys']), mt_rand(1, 3) > 2 ? 2 : 1));
                foreach ($picks as $name) {
                    if (!isset($products[$name])) continue;
                    $p = $products[$name];
                    if ($p['design_type'] && mt_rand(1, 10) <= 7) {
                        $items[] = $this->designItem($c, $p, $createdAt);
                    } else {
                        $items[] = ['product_id' => (int) $p['product_id'], 'customization_id' => null,
                                    'qty' => mt_rand(1, 8), 'unit_price' => (float) $p['base_price']];
                    }
                }
                if (!$items) continue;
                $subtotal = round(array_sum(array_map(fn($it) => $it['qty'] * $it['unit_price'], $items)), 2);

                // Stage of the order, by how old it is
                $status = $this->statusFor($ago);
                $option = mt_rand(1, 100) <= 25 ? 'express' : 'standard';
                $carrier = mt_rand(1, 100) <= 72 ? 'packe' : ['uber', 'yango', 'bolt'][$partnerTurn++ % 3];   // partners take turns
                $km = $this->roadKm($c['lat'], $c['lng']);
                $fees = DeliveryPricing::fees($km);
                $isPro = $c['pro'] && strtotime($createdAt) >= strtotime($c['pro_since']);
                $quote = ['standard' => $fees['standard'], 'express' => $fees['express']];
                if ($isPro) $quote = ['standard' => 0.0, 'standard_full' => $fees['standard'], 'express' => $fees['express']];
                $fee = Couriers::fee($quote, $option, $carrier);
                $discount = $isPro ? round($subtotal * ProController::DISCOUNT_RATE, 2) : 0.0;
                $total = round($subtotal - $discount + $fee, 2);

                do { $invoice = 'PK-' . mt_rand(100000, 999999); } while ($this->model->invoiceExists($invoice));
                $this->model->addOrder([
                    'customer_id' => $c['id'], 'invoice' => $invoice, 'subtotal' => $subtotal, 'discount' => $discount,
                    'fee' => $fee, 'total' => $total, 'status' => $status, 'reference' => DemoDataClass::REF_PREFIX . $invoice,
                    'created_at' => $createdAt, 'channel' => mt_rand(1, 10) <= 7 ? 'mobile_money' : 'card', 'items' => $items,
                    'delivery' => $this->delivery($c, $status, $option, $carrier, $km, $riders),
                ]);
                $made++;
                if ($status !== 'cancelled') $revenue += $total;
            }
            $this->model->commit();
            return ['success' => true, 'orders' => $made, 'customers' => count($customers), 'revenue' => $revenue];
        } catch (Throwable $e) {
            $this->model->rollback();
            log_error('Demo data load failed: ' . $e->getMessage());
            return Validate::fail('The demo data could not be loaded. Run setup.php first, then try again.');
        }
    }

    /** A saved 3D design for this product, priced by the real pricing rules. */
    private function designItem(array $c, array $p, string $createdAt): array
    {
        $type = $p['design_type'];
        $t = DesignTypes::TYPES[$type];
        $preset = $t['presets'][mt_rand(0, count($t['presets']) - 1)][1];
        [$L, $W, $H] = DesignTypes::normalise($type, ...array_pad($preset, 3, 0));
        $qtys = $t['qtys'];
        $qty = $qtys[min(count($qtys) - 1, (int) floor(pow(mt_rand(0, 1000) / 1000, 1.6) * count($qtys)))];   // smaller runs more often
        $materials = array_keys($t['materials'] ?? ['kraft' => 1]);
        $material = $materials[mt_rand(0, count($materials) - 1)];
        $pattern = mt_rand(1, 10) <= 2 ? array_rand(DesignTypes::PATTERNS) : null;
        $colour = $pattern ? null : self::COLOURS[mt_rand(0, count(self::COLOURS) - 1)];
        $unit = DesignTypes::unitPrice($type, $L, $W, $H, $material, $colour !== null, true, $qty, $pattern !== null);
        $font = array_rand(DesignTypes::FONTS);
        $designId = $this->model->addDesign($c['id'], (int) $p['product_id'], [
            'L' => $L, 'W' => $W, 'H' => $H, 'material' => $material, 'colour' => $colour, 'text' => mb_substr($c['business'], 0, 28),
            'font' => $font, 'pattern' => $pattern, 'qty' => $qty, 'unit_price' => $unit,
        ], $createdAt);
        return ['product_id' => (int) $p['product_id'], 'customization_id' => $designId, 'qty' => $qty, 'unit_price' => $unit];
    }

    private function statusFor(int $ago): string
    {
        $r = mt_rand(1, 100);
        if ($ago <= 1)  return $r <= 60 ? 'paid' : 'processing';
        if ($ago <= 3)  return $r <= 50 ? 'processing' : 'ready';
        if ($ago <= 6)  return $r <= 45 ? 'ready' : 'out_for_delivery';
        if ($ago <= 9)  return $r <= 40 ? 'out_for_delivery' : 'delivered';
        return $r <= 88 ? 'delivered' : 'cancelled';
    }

    private function delivery(array $c, string $status, string $option, string $carrier, float $km, array $riders): array
    {
        $dStatus = ['paid' => 'pending', 'processing' => 'assigned', 'ready' => 'assigned',
                    'out_for_delivery' => 'in_transit', 'delivered' => 'delivered', 'cancelled' => 'pending'][$status];
        $booked = $dStatus !== 'pending';
        $partner = Couriers::isPartner($carrier);
        $driver = self::PARTNER_DRIVERS[mt_rand(0, count(self::PARTNER_DRIVERS) - 1)];
        return [
            'recipient' => $c['contact'], 'phone' => $c['phone'], 'address' => $c['address'], 'city' => $c['area'],
            'option' => $option, 'carrier' => $carrier, 'km' => $km,
            'lat' => $c['lat'], 'lng' => $c['lng'], 'status' => $dStatus,
            'rider' => (!$partner && $booked && $riders) ? (int) $riders[mt_rand(0, count($riders) - 1)] : null,
            'driver_name' => ($partner && $booked) ? $driver : null,
            'driver_phone' => ($partner && $booked) ? sprintf('05590002%02d', mt_rand(1, 99)) : null,
            'tracking_url' => ($partner && $booked) ? Couriers::get($carrier)['app'] : null,
        ];
    }

    /** Road distance estimate from Packe's workshop: straight line × 1.3, like DeliveryPricing's fallback. */
    private function roadKm(float $lat, float $lng): float
    {
        $r = fn($d) => deg2rad($d);
        $dLat = $r($lat - DELIVERY_ORIGIN_LAT);
        $dLng = $r($lng - DELIVERY_ORIGIN_LNG);
        $h = sin($dLat / 2) ** 2 + cos($r(DELIVERY_ORIGIN_LAT)) * cos($r($lat)) * sin($dLng / 2) ** 2;
        return round(2 * 6371 * asin(sqrt($h)) * 1.3, 1);
    }
}
