<?php
/**
 * Settings → Cookies screen. Values are prepared by cookie_consent_page() in admin.php.
 * @var string $environment ('' = production)  @var bool $testMode  @var string $privacySettingsUrl ('' = privacy page is set)
 * @var array<int, array{title: string, embeds: bool, services: array<int, array{key: string, label: string, value: string, placeholder: string}>}> $groups
 * @var bool $embeds
 * @var array{value: string, placeholder: string} $message (placeholder = automatically composed message)
 * @var array<int, array{key: string, label: string, value: string, placeholder: string}> $texts
 * @var string $btnBg  @var string $btnFg  @var bool $fullWidth  @var int $fontSize  @var int $version
 */
?>
<div class="wrap">
    <h1>Cookie banner</h1>
    <?php if ($environment) : ?>
        <div class="notice <?= $testMode ? 'notice-warning' : 'notice-info' ?> inline"><p>
            This is not a production environment (<code><?= esc_html($environment) ?></code>).
            <?php if ($testMode) : ?>
                <strong>Test mode is on: services load after consent and send data to the accounts entered below.</strong>
            <?php else : ?>
                The banner works, but services do not load (you can change this with “Test mode” at the bottom).
            <?php endif; ?></p></div>
    <?php endif; ?>
    <?php if ($privacySettingsUrl) : ?>
        <div class="notice notice-warning inline"><p>No privacy policy page is set, so the banner has no link to it.
            Set it in <a href="<?= esc_url($privacySettingsUrl) ?>">Settings → Privacy</a>.</p></div>
    <?php endif; ?>
    <form method="post" action="options.php">
        <?php settings_fields('cookie_consent'); ?>
        <p>Enter IDs only for the services the site uses. The banner shows categories that have at least one service and does not
            appear at all without any. Remove these services' tags from the theme and other plugins, otherwise they run without consent.</p>
        <table class="form-table" role="presentation">
            <?php foreach ($groups as $group) : ?>
                <tr><th colspan="2"><h2 style="margin:0"><?= esc_html($group['title']) ?></h2></th></tr>
                <?php foreach ($group['services'] as $service) : ?>
                    <tr>
                        <th scope="row"><label for="cc-<?= esc_attr($service['key']) ?>"><?= esc_html($service['label']) ?></label></th>
                        <td>
                            <input type="text" id="cc-<?= esc_attr($service['key']) ?>" name="cookie_consent[<?= esc_attr($service['key']) ?>]"
                                   value="<?= esc_attr($service['value']) ?>" class="regular-text code" placeholder="<?= esc_attr($service['placeholder']) ?>">
                            <?php if ($service['key'] === 'gtm') : ?>
                                <p class="description">Loads after consent to analytics or marketing. Google tags in the container respect
                                    Consent Mode on their own. Fire third-party tags on the <code>cc_consent_update</code> event with a condition on the
                                    data layer variable <code>cc_marketing</code> / <code>cc_analytics</code> = <code>true</code>. If the container includes
                                    services not listed here, mention them in the message.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($group['embeds']) : ?>
                    <tr>
                        <th scope="row">YouTube and Google Maps</th>
                        <td><label><input type="checkbox" name="cookie_consent[embeds]" value="1" <?php checked($embeds); ?>>
                            Block embedded YouTube videos and Google Maps until consent is given</label>
                            <p class="description">A placeholder with an accept button is shown instead.</p></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>

            <tr><th colspan="2"><h2 style="margin:0">Texts</h2>
                <p class="description" style="font-weight:normal">Leave a field empty to use the default English text (shown in grey).</p></th></tr>
            <tr>
                <th scope="row"><label for="cc-text-message">Message</label></th>
                <td>
                    <textarea id="cc-text-message" name="cookie_consent[texts][message]" rows="4" class="large-text"
                              placeholder="<?= esc_attr($message['placeholder']) ?>"><?= esc_textarea($message['value']) ?></textarea>
                    <p class="description">Empty = composed automatically from the enabled services. <code>{services}</code> and
                        <code>{providers}</code> are replaced with the current lists. A custom message must state the purposes, the providers,
                        data transfers to the USA and how to change consent. The privacy policy link is added automatically.
                        Allowed: <code>&lt;a&gt; &lt;strong&gt; &lt;em&gt; &lt;br&gt;</code>.</p>
                </td>
            </tr>
            <?php foreach ($texts as $text) : ?>
                <tr>
                    <th scope="row"><label for="cc-text-<?= esc_attr($text['key']) ?>"><?= esc_html($text['label']) ?></label></th>
                    <td><input type="text" id="cc-text-<?= esc_attr($text['key']) ?>" name="cookie_consent[texts][<?= esc_attr($text['key']) ?>]"
                               value="<?= esc_attr($text['value']) ?>" class="large-text" placeholder="<?= esc_attr($text['placeholder']) ?>"></td>
                </tr>
            <?php endforeach; ?>

            <tr><th colspan="2"><h2 style="margin:0">Appearance</h2></th></tr>
            <tr>
                <th scope="row">Button colours</th>
                <td>
                    <label>background <input type="color" name="cookie_consent[btn_bg]" value="<?= esc_attr($btnBg) ?>"></label>
                    &nbsp; <label>text <input type="color" name="cookie_consent[btn_fg]" value="<?= esc_attr($btnFg) ?>"></label>
                    <p class="description">“Reject” and “Accept” always look the same: refusing must not be less visible than accepting.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Width</th>
                <td><label><input type="checkbox" name="cookie_consent[full_width]" value="1" <?php checked($fullWidth); ?>>
                    Full-width bar (instead of a box up to 720 px)</label></td>
            </tr>
            <tr>
                <th scope="row"><label for="cc-font">Font size</label></th>
                <td><input type="number" id="cc-font" name="cookie_consent[font_size]" value="<?= (int) $fontSize ?>" min="10" max="24" class="small-text"> px</td>
            </tr>
            <tr>
                <th scope="row">Ask again</th>
                <td><label><input type="checkbox" name="cookie_consent[reconsent]" value="1"> Ask everyone for consent again after saving</label>
                    <p class="description">Tick this after a significant change to the texts. Changing services does it automatically.
                        Current consent version: <?= (int) $version ?>.</p></td>
            </tr>
            <?php if ($environment) : ?>
                <tr>
                    <th scope="row">Test mode</th>
                    <td><label><input type="checkbox" name="cookie_consent[test_mode]" value="1" <?php checked($testMode); ?>>
                        Load services outside production too</label>
                        <p class="description">Data will be sent to the accounts entered above. Use test accounts, not production ones.</p></td>
                </tr>
            <?php endif; ?>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
