<?php
/**
 * The packaging templates the 3D designer can build, and how each one is sized and priced.
 * A product's `design_type` column names one of these keys (NULL = no designer).
 * The same table is sent to js/designer.js, so the live estimate and the server price use one set of rules.
 *
 * Sizes are stored in customizations.length_cm / width_cm / height_cm; `fields` says what each one means
 * for a template (for a pouch "height" is the gusset, for a catalogue it is the page count).
 * Owner: Josephine Allan
 */
class DesignTypes
{
    /** Price factor per quantity step (the first step is full price). */
    public const FACTORS = [1.00, 0.88, 0.76, 0.66, 0.56];

    private const BOX_QTYS   = [50, 100, 250, 500, 1000];
    private const SMALL_QTYS = [250, 500, 1000, 2500, 5000];

    public const TYPES = [
        'mailer' => [
            'label' => 'Mailer box', 'noun' => ['box', 'boxes'], 'folds' => true,
            'fields' => ['length' => ['Length', 10, 60, 30], 'width' => ['Width', 8, 50, 22], 'height' => ['Height', 3, 30, 10]],
            'presets' => [['Small', [20, 15, 6]], ['Medium', [30, 22, 10]], ['Large', [40, 30, 15]]],
            'materials' => ['kraft' => 'Kraft board', 'white' => 'White board'],
            'base' => 1.20, 'rate' => 0.0011, 'ink' => 0.90, 'brand' => 0.60, 'qtys' => self::BOX_QTYS,
            'blurb' => 'Set the size, pick a board, add your logo, and watch it fold up in 3D.',
        ],
        'carton' => [
            'label' => 'Shipping carton', 'noun' => ['box', 'boxes'], 'folds' => true,
            'fields' => ['length' => ['Length', 15, 80, 40], 'width' => ['Width', 10, 60, 30], 'height' => ['Height', 8, 60, 25]],
            'presets' => [['Small', [30, 20, 15]], ['Medium', [40, 30, 25]], ['Large', [60, 40, 40]]],
            'materials' => ['kraft' => 'Brown corrugated', 'white' => 'White corrugated'],
            'base' => 2.00, 'rate' => 0.0010, 'ink' => 1.00, 'brand' => 0.60, 'qtys' => self::BOX_QTYS,
            'blurb' => 'A strong shipping carton with flaps on top and bottom. Print your brand on the sides.',
        ],
        'foodbox' => [
            'label' => 'Takeaway food box', 'noun' => ['box', 'boxes'], 'folds' => true,
            'fields' => ['length' => ['Length', 8, 30, 17], 'width' => ['Width', 6, 22, 12], 'height' => ['Height', 4, 12, 6.5]],
            'presets' => [['Small', [13, 10, 5]], ['Medium', [17, 12, 6.5]], ['Large', [21, 15, 6.5]]],
            'materials' => ['kraft' => 'Food-safe kraft', 'white' => 'White food board'],
            'base' => 0.55, 'rate' => 0.0010, 'ink' => 0.25, 'brand' => 0.20, 'qtys' => self::BOX_QTYS,
            'blurb' => 'A fold-top takeaway box. Your logo goes on a round sticker that seals the lid; the print colour is the sticker colour.',
        ],
        'pouch' => [
            'label' => 'Stand-up pouch', 'noun' => ['pouch', 'pouches'], 'folds' => false,
            'fields' => ['length' => ['Width', 8, 30, 16], 'width' => ['Height', 10, 40, 24], 'height' => ['Gusset', 3, 12, 8]],
            'presets' => [['Small', [12, 18, 6]], ['Medium', [16, 24, 8]], ['Large', [22, 32, 10]]],
            'materials' => ['kraft' => 'Kraft paper', 'white' => 'White matte'],
            'base' => 0.70, 'rate' => 0.0016, 'ink' => 0.35, 'brand' => 0.25, 'qtys' => self::BOX_QTYS,
            'blurb' => 'A stand-up pouch with a clear window and zip top. The gusset is the fold-out base.',
        ],
        'label' => [
            'label' => 'Rectangular label', 'noun' => ['label', 'labels'], 'folds' => false,
            'fields' => ['length' => ['Width', 3, 20, 10], 'width' => ['Height', 2, 15, 6]],
            'presets' => [['Small', [7, 4]], ['Medium', [10, 6]], ['Large', [15, 10]]],
            'materials' => ['white' => 'White paper', 'kraft' => 'Kraft paper'],
            'base' => 0.05, 'rate' => 0.0040, 'ink' => 0.03, 'brand' => 0.02, 'qtys' => self::SMALL_QTYS,
            'blurb' => 'Printed labels on a peel-off sheet: size, paper, colour and your logo or text.',
        ],
        'sticker' => [
            'label' => 'Round sticker', 'noun' => ['sticker', 'stickers'], 'folds' => false,
            'fields' => ['length' => ['Diameter', 2, 15, 6]],
            'presets' => [['Small', [4]], ['Medium', [6]], ['Large', [10]]],
            'materials' => ['white' => 'White gloss vinyl', 'kraft' => 'Kraft paper'],
            'base' => 0.04, 'rate' => 0.0045, 'ink' => 0.03, 'brand' => 0.02, 'qtys' => self::SMALL_QTYS,
            'blurb' => 'Round stickers for sealing bags and branding products.',
        ],
        'seal' => [
            'label' => 'Tamper seal', 'noun' => ['seal', 'seals'], 'folds' => false,
            'fields' => ['length' => ['Length', 4, 20, 10], 'width' => ['Width', 1.5, 6, 3]],
            'presets' => [['Small', [8, 2.5]], ['Medium', [10, 3]], ['Large', [15, 4]]],
            'materials' => ['white' => 'White vinyl', 'kraft' => 'Kraft paper'],
            'base' => 0.05, 'rate' => 0.0050, 'ink' => 0.03, 'brand' => 0.02, 'qtys' => self::SMALL_QTYS,
            'blurb' => 'Seal strips with tear slits, so customers can see if a parcel was opened.',
        ],
        'card' => [
            'label' => 'Card', 'noun' => ['card', 'cards'], 'folds' => false,
            'fields' => ['length' => ['Width', 8, 21, 14.8], 'width' => ['Height', 5, 15, 10.5]],
            'presets' => [['Business', [9, 5.5]], ['A6', [14.8, 10.5]], ['DL', [21, 9.9]]],
            'materials' => ['white' => 'White card', 'kraft' => 'Kraft card'],
            'base' => 0.15, 'rate' => 0.0030, 'ink' => 0.08, 'brand' => 0.05, 'qtys' => self::BOX_QTYS,
            'blurb' => 'Thank-you and insert cards: your colour and logo on the front.',
        ],
        'brochure' => [
            'label' => 'Tri-fold brochure', 'noun' => ['brochure', 'brochures'], 'folds' => true,
            'fields' => ['length' => ['Open width', 20, 42, 29.7], 'width' => ['Height', 10, 30, 21]],
            'presets' => [['A4', [29.7, 21]], ['Letter', [27.9, 21.6]], ['Long', [35, 21]]],
            'materials' => ['white' => 'White gloss', 'kraft' => 'Kraft paper'],
            'base' => 0.35, 'rate' => 0.0012, 'ink' => 0.15, 'brand' => 0.10, 'qtys' => self::BOX_QTYS,
            'blurb' => 'A sheet folded into three panels. Your logo goes on the cover panel.',
        ],
        'catalog' => [
            'label' => 'Catalogue', 'noun' => ['catalogue', 'catalogues'], 'folds' => true,
            'fields' => ['length' => ['Page width', 10, 30, 14.8], 'width' => ['Page height', 10, 30, 21], 'height' => ['Pages', 8, 64, 24]],
            'presets' => [['A5, 24 pages', [14.8, 21, 24]], ['A4, 16 pages', [21, 29.7, 16]], ['Square, 32 pages', [20, 20, 32]]],
            'materials' => ['white' => 'White gloss', 'kraft' => 'Kraft cover'],
            'base' => 1.50, 'rate' => 0.00045, 'ink' => 0.60, 'brand' => 0.30, 'qtys' => self::BOX_QTYS,
            'blurb' => 'A stapled catalogue: page size, page count and a branded cover.',
        ],
    ];

    /** Fonts customers can choose for the text on their packaging: key => [name shown, font family, weight]. */
    public const FONTS = [
        'archivo'    => ['Modern', 'Archivo', 800],
        'montserrat' => ['Clean', 'Montserrat', 700],
        'playfair'   => ['Elegant', 'Playfair Display', 700],
        'bebas'      => ['Bold', 'Bebas Neue', 400],
        'pacifico'   => ['Script', 'Pacifico', 400],
        'caveat'     => ['Handwritten', 'Caveat', 700],
    ];
    public const DEFAULT_FONT = 'archivo';

    /**
     * Standard patterns customers can print all over the outside (pictures in images/patterns/<key>.jpg).
     * A pattern is full-colour printing, so it costs PATTERN_FACTOR times the template's print-colour charge.
     */
    public const PATTERNS = [
        'henna-swirl'       => 'Henna Swirl',
        'midnight-meadow'   => 'Midnight Meadow',
        'confetti-branches' => 'Confetti Branches',
        'bauhaus-blocks'    => 'Bauhaus Blocks',
        'bold-bloom'        => 'Bold Bloom',
        'tropical-night'    => 'Tropical Night',
        'hexagon-garden'    => 'Hexagon Garden',
        'ocean-waves'       => 'Ocean Waves',
        'rainbow-rings'     => 'Rainbow Rings',
    ];
    public const PATTERN_FACTOR = 1.5;

    public static function patternName(?string $pattern): ?string
    {
        return self::PATTERNS[$pattern ?? ''] ?? null;
    }

    /** The Google Fonts stylesheet for every design font (pages with the 3D designer or showcase). */
    public static function fontsLink(): string
    {
        $families = [];
        foreach (self::FONTS as [, $family, $weight]) {
            $families[] = 'family=' . str_replace(' ', '+', $family) . ':wght@' . $weight;
        }
        return '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap">';
    }

    public static function fontName(?string $font): string
    {
        return self::FONTS[$font ?? ''][0] ?? self::FONTS[self::DEFAULT_FONT][0];
    }

    public static function get(?string $type): ?array
    {
        return ($type !== null && isset(self::TYPES[$type])) ? self::TYPES[$type] : null;
    }

    /** Options for the admin product form. */
    public static function options(): array
    {
        return array_map(fn($t) => $t['label'], self::TYPES);
    }

    /** Printed area in cm² (what the price is based on). Mirrors area() in js/designer.js. */
    public static function area(string $type, float $L, float $W, float $H): float
    {
        switch ($type) {
            case 'mailer':   // the flat sheet of the mailer box the designer draws
                $lidD = $W - 0.4;
                return $L * $W + 2 * $L * $H + 2 * $H * $W + $L * $lidD + ($L - 0.8) * $H * 0.8 + 2 * ($H * 0.85) * ($lidD * 0.8);
            case 'carton':   // regular slotted carton: four walls in a strip, half-width flaps top and bottom
                return (2 * $L + 2 * $W + 3) * ($H + $W);
            case 'foodbox':  // walls lean out ~10°, so the top is wider; lid flaps cover the top
                $g = $H * sin(0.18);
                $Lt = $L + 2 * $g;
                $Wt = $W + 2 * $g;
                return $L * $W + $H * ($L + $Lt + $W + $Wt) + 1.46 * $Lt * $Wt;
            case 'pouch':    // front, back and the gusset base
                return 2 * $L * $W + $L * $H;
            case 'sticker':
                return M_PI * ($L / 2) ** 2;
            case 'catalog':  // every page
                return $L * $W * $H;
            default:         // flat pieces
                return $L * $W;
        }
    }

    /** Price per unit for the quantity step $qty. Mirrors unitPrice() in js/designer.js. */
    public static function unitPrice(string $type, float $L, float $W, float $H, string $material,
                                     bool $hasInk, bool $hasBranding, int $qty, bool $hasPattern = false): float
    {
        $t = self::TYPES[$type];
        $u = $t['base'] + self::area($type, $L, $W, $H) * $t['rate'] * ($material === 'white' ? 1.15 : 1.0);
        if ($hasPattern) $u += $t['ink'] * self::PATTERN_FACTOR;   // full-colour pattern instead of one print colour
        elseif ($hasInk) $u += $t['ink'];
        if ($hasBranding) $u += $t['brand'];
        $step = array_search($qty, $t['qtys'], true);
        return round($u * self::FACTORS[$step === false ? 0 : $step], 2);
    }

    /** "From" price shown on product cards: the smallest preset with a logo, at the biggest quantity. */
    public static function fromPrice(string $type): float
    {
        $t = self::TYPES[$type];
        $s = array_pad($t['presets'][0][1], 3, 0);
        [$L, $W, $H] = self::normalise($type, $s[0], $s[1], $s[2]);
        return self::unitPrice($type, $L, $W, $H, 'kraft', false, true, end($t['qtys']));
    }

    /** Fill in the sizes a template doesn't ask for (a sticker is as wide as it is long; flat things have no height). */
    public static function normalise(string $type, float $L, float $W, float $H): array
    {
        $f = self::TYPES[$type]['fields'];
        if (!isset($f['width'])) $W = $L;
        if (!isset($f['height'])) $H = 0;
        return [$L, $W, $H];
    }

    /** Short size text for carts, invoices and saved designs. */
    public static function describeSize(?string $type, $L, $W, $H): string
    {
        $n = fn($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        switch ($type) {
            case 'sticker': return 'Ø ' . $n($L) . ' cm';
            case 'pouch':   return $n($L) . ' × ' . $n($W) . ' cm, ' . $n($H) . ' cm gusset';
            case 'catalog': return $n($L) . ' × ' . $n($W) . ' cm, ' . (int) $H . ' pages';
            case 'label': case 'seal': case 'card': case 'brochure':
                return $n($L) . ' × ' . $n($W) . ' cm';
            default:        return $n($L) . ' × ' . $n($W) . ' × ' . $n($H) . ' cm';
        }
    }

    public static function materialName(?string $type, string $material): string
    {
        $t = self::get($type) ?? self::TYPES['mailer'];
        return $t['materials'][$material] ?? ucfirst($material);
    }

    public static function nounPlural(?string $type): string
    {
        return (self::get($type) ?? self::TYPES['mailer'])['noun'][1];
    }
}
