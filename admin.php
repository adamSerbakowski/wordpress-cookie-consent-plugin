<?php
// Settings → Cookies screen: saving the settings and preparing values for templates/admin.php.

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function (): void {
    register_setting('cookie_consent', 'cookie_consent', ['sanitize_callback' => 'cookie_consent_sanitize']);
});

add_action('admin_menu', function (): void {
    add_options_page('Cookie banner', 'Cookies', 'manage_options', 'cookie-consent', 'cookie_consent_page');
});

add_filter('plugin_action_links_' . plugin_basename(__DIR__ . '/cookie-consent.php'), function ($links): array {
    array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=cookie-consent')) . '">Settings</a>');
    return $links;
});

function cookie_consent_sanitize($in): array
{
    $old = cookie_consent_settings();
    $out = $old;

    foreach (COOKIE_CONSENT_SERVICES as $key => $svc) {
        $id = trim($in[$key] ?? '');
        $id = $key === 'clarity' ? strtolower($id) : strtoupper($id);
        if ($id === '' || preg_match($svc[3], $id)) {
            $out[$key] = $id;
        } else {
            add_settings_error('cookie_consent', $key, sprintf('%s: the ID should look like %s. The previous value was kept.', $svc[0], $svc[4]));
        }
    }
    $out['embeds'] = empty($in['embeds']) ? 0 : 1;
    $texts = ['message' => trim(wp_kses($in['texts']['message'] ?? '', COOKIE_CONSENT_ALLOWED_HTML))];
    foreach (array_keys(COOKIE_CONSENT_TEXTS) as $key) {
        $texts[$key] = sanitize_text_field($in['texts'][$key] ?? '');
    }
    $out['texts'] = array_filter($texts, 'strlen');
    $out['btn_bg'] = sanitize_hex_color($in['btn_bg'] ?? '') ?: $old['btn_bg'];
    $out['btn_fg'] = sanitize_hex_color($in['btn_fg'] ?? '') ?: $old['btn_fg'];
    $out['full_width'] = empty($in['full_width']) ? 0 : 1;
    $out['font_size'] = min(24, max(10, (int) ($in['font_size'] ?? $old['font_size'])));
    $out['test_mode'] = empty($in['test_mode']) ? 0 : 1;
    // Ask again: forced by the checkbox or automatic when services change (the consent covered a different scope).
    $services = array_flip(array_keys(COOKIE_CONSENT_SERVICES));
    $changed = array_diff_assoc(array_intersect_key($out, $services), array_intersect_key($old, $services))
        || $out['embeds'] !== (int) $old['embeds'];
    if (!empty($in['reconsent']) || $changed) {
        $out['version'] = (int) $old['version'] + 1;
    }
    return $out;
}

function cookie_consent_page(): void
{
    $s = cookie_consent_settings();
    $env = function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production';
    $groups = [];
    foreach (['a' => 'Analytics', 'm' => 'Marketing', 'f' => 'Functional', '' => 'Tag Manager'] as $cat => $title) {
        $services = [];
        foreach (COOKIE_CONSENT_SERVICES as $key => $svc) {
            if ($svc[2] === $cat) {
                $services[] = ['key' => $key, 'label' => $svc[0], 'value' => $s[$key], 'placeholder' => $svc[4]];
            }
        }
        $groups[] = ['title' => $title, 'services' => $services, 'embeds' => $cat === 'f'];
    }
    $texts = [];
    foreach (COOKIE_CONSENT_TEXTS as $key => [$label, $default]) {
        $texts[] = ['key' => $key, 'label' => $label, 'value' => $s['texts'][$key] ?? '', 'placeholder' => $default];
    }
    echo cookie_consent_render('admin', [
        'environment' => $env === 'production' ? '' : $env,
        'testMode' => (bool) $s['test_mode'],
        'privacySettingsUrl' => get_privacy_policy_url() ? '' : admin_url('options-privacy.php'),
        'groups' => $groups,
        'embeds' => (bool) $s['embeds'],
        'message' => [
            'value' => $s['texts']['message'] ?? '',
            'placeholder' => cookie_consent_active($s) ? cookie_consent_message(['texts' => []] + $s) : '',
        ],
        'texts' => $texts,
        'btnBg' => $s['btn_bg'],
        'btnFg' => $s['btn_fg'],
        'fullWidth' => (bool) $s['full_width'],
        'fontSize' => (int) $s['font_size'],
        'version' => (int) $s['version'],
    ]);
}
