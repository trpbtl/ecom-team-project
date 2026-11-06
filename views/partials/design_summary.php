<?php
/** Short description of a design. Expects $it with design_type, length_cm, width_cm, height_cm, material, print_colour, logo_file, brand_text. */
require_once __DIR__ . '/../../controllers/DesignTypes.php';
?>
<?php if (!empty($it['length_cm'])): ?>
    <p class="text-sm text-muted mt-1">
        <?= e(DesignTypes::describeSize($it['design_type'] ?? null, $it['length_cm'], $it['width_cm'], $it['height_cm'])) ?>
        · <?= e(DesignTypes::materialName($it['design_type'] ?? null, $it['material'])) ?>
        <?php if ($it['print_colour']): ?>
            · <span class="inline-block w-3 h-3 rounded-full align-middle border border-line" style="background: <?= e($it['print_colour']) ?>"></span> print
        <?php endif; ?>
        <?php if ($it['logo_file']): ?> · logo<?php endif; ?>
        <?php if ($it['brand_text']): ?> · “<?= e($it['brand_text']) ?>”<?php endif; ?>
    </p>
<?php endif; ?>
