<?php /** Short description of a designed box. Expects $it with length_cm, width_cm, height_cm, material, print_colour, logo_file, brand_text. */ ?>
<?php if (!empty($it['length_cm'])): ?>
    <p class="text-sm text-muted mt-1">
        <?= rtrim(rtrim((string) $it['length_cm'], '0'), '.') ?> × <?= rtrim(rtrim((string) $it['width_cm'], '0'), '.') ?> × <?= rtrim(rtrim((string) $it['height_cm'], '0'), '.') ?> cm
        · <?= $it['material'] === 'white' ? 'White' : 'Kraft' ?> board
        <?php if ($it['print_colour']): ?>
            · <span class="inline-block w-3 h-3 rounded-full align-middle border border-line" style="background: <?= e($it['print_colour']) ?>"></span> print
        <?php endif; ?>
        <?php if ($it['logo_file']): ?> · logo<?php endif; ?>
        <?php if ($it['brand_text']): ?> · “<?= e($it['brand_text']) ?>”<?php endif; ?>
    </p>
<?php endif; ?>
