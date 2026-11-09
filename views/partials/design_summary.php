<?php
/** Short description of a design. Expects $it with design_type, length_cm, width_cm, height_cm, material, print_colour, logo_file, brand_text, font, text_size, text_colour, text_italic. */
require_once __DIR__ . '/../../controllers/DesignTypes.php';
?>
<?php if (!empty($it['length_cm'])): ?>
    <p class="text-sm text-muted mt-1">
        <?= e(DesignTypes::describeSize($it['design_type'] ?? null, $it['length_cm'], $it['width_cm'], $it['height_cm'])) ?>
        · <?= e(DesignTypes::materialName($it['design_type'] ?? null, $it['material'])) ?>
        <?php if ($it['print_colour']): ?>
            · <span class="inline-block w-3 h-3 rounded-full align-middle border border-line" style="background: <?= e($it['print_colour']) ?>"></span> print
        <?php endif; ?>
        <?php if ($__pattern = DesignTypes::patternName($it['pattern'] ?? null)): ?>
            · <span class="inline-block w-3 h-3 rounded-sm align-middle border border-line bg-cover" style="background-image: url('<?= e(url('images/patterns/' . $it['pattern'] . '.jpg')) ?>')"></span>
            <?= e($__pattern) ?> pattern<?= (int) ($it['pattern_scale'] ?? 100) !== 100 ? ' (' . (int) $it['pattern_scale'] . '% size)' : '' ?>
        <?php endif; ?>
        <?php if ($it['logo_file']): ?> · logo<?php endif; ?>
        <?php if ($it['brand_text']): ?>
            · “<?= e($it['brand_text']) ?>” in <?= e(DesignTypes::fontName($it['font'] ?? null)) ?> font<?=
                !empty($it['text_italic']) ? ', italic' : '' ?><?=
                (int) ($it['text_size'] ?? 100) !== 100 ? ', ' . (int) $it['text_size'] . '% size' : '' ?><?php if (!empty($it['text_colour'])): ?>,
                <span class="inline-block w-3 h-3 rounded-full align-middle border border-line" style="background: <?= e($it['text_colour']) ?>"></span> text colour
            <?php endif; ?>
        <?php endif; ?>
    </p>
<?php endif; ?>
