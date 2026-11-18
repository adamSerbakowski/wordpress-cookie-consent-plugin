<?php
/**
 * Consent banner and the reopen button. Values are prepared by the wp_footer hook in cookie-consent.php.
 * @var bool $fullWidth  @var string $style  @var string $ariaBanner  @var string $message (HTML: COOKIE_CONSENT_ALLOWED_HTML)
 * @var string $privacyUrl ('' = no link)  @var string $privacyLink
 * @var array<int, array{key: string, name: string, services: string, desc: string}> $categories ([] = single category, no settings)
 * @var string $legend  @var string $necessaryName  @var string $necessaryDesc
 * @var string $btnSettings  @var string $btnSave  @var string $btnReject  @var string $btnAccept  @var string $btnOpen  @var string $ariaOpen
 */
?>
<div id="cc-banner" class="cc-banner<?= $fullWidth ? ' cc-full' : '' ?>" style="<?= esc_attr($style) ?>"
     role="region" aria-label="<?= esc_attr($ariaBanner) ?>" hidden>
    <p class="cc-text">
        <?= wp_kses($message, COOKIE_CONSENT_ALLOWED_HTML) ?>
        <?php if ($privacyUrl) : ?><a href="<?= esc_url($privacyUrl) ?>"><?= esc_html($privacyLink) ?></a><?php endif; ?>
    </p>
    <?php if ($categories) : ?>
        <fieldset class="cc-cats" hidden>
            <legend><?= esc_html($legend) ?></legend>
            <label><input type="checkbox" checked disabled> <strong><?= esc_html($necessaryName) ?></strong> – <?= esc_html($necessaryDesc) ?></label>
            <?php foreach ($categories as $category) : ?>
                <label><input type="checkbox" data-cc-cat="<?= esc_attr($category['key']) ?>">
                    <strong><?= esc_html($category['name']) ?></strong> (<?= esc_html($category['services']) ?>) – <?= esc_html($category['desc']) ?></label>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>
    <div class="cc-buttons">
        <?php if ($categories) : ?>
            <button type="button" class="cc-link" data-cc="settings" aria-expanded="false"><?= esc_html($btnSettings) ?></button>
            <button type="button" class="cc-btn" data-cc="save" hidden><?= esc_html($btnSave) ?></button>
        <?php endif; ?>
        <button type="button" class="cc-btn" data-cc="denied"><?= esc_html($btnReject) ?></button>
        <button type="button" class="cc-btn" data-cc="granted"><?= esc_html($btnAccept) ?></button>
    </div>
</div>
<button type="button" id="cc-open" class="cc-open" data-cc="open" aria-label="<?= esc_attr($ariaOpen) ?>" hidden><?= esc_html($btnOpen) ?></button>
