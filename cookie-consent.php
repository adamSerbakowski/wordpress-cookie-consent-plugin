<?php
/**
 * Plugin Name: Cookie Consent
 * Plugin URI: https://github.com/adamSerbakowski/wordpress-cookie-consent-plugin
 * Description: Cookie consent banner with categories (analytics, marketing, functional) and Google Consent Mode v2. Popular services (GA4, GTM, Google Ads, Meta, TikTok, LinkedIn, Clarity, Hotjar) load only after consent. Settings: Settings → Cookies.
 * Version: 2.2.2
 * Requires PHP: 7.1
 * Author: Adam Serbakowski
 * Author URI: https://github.com/adamSerbakowski
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * Copyright (c) 2026 Adam Serbakowski. Released under the MIT License (see LICENSE).
 */

if (!defined('ABSPATH')) {
    exit;
}

const COOKIE_CONSENT_ASSETS = '2.2.2';

// HTML allowed in the message.
const COOKIE_CONSENT_ALLOWED_HTML = ['a' => ['href' => true, 'target' => true, 'rel' => true], 'strong' => [], 'em' => [], 'br' => []];

// Consent categories: key => word used in the default message (“for … purposes”). Names and descriptions are in the texts.
const COOKIE_CONSENT_CATEGORIES = ['a' => 'analytics', 'm' => 'marketing', 'f' => 'functional'];

// Supported services: key => [name, provider, category, ID pattern, example ID].
// Google Tag Manager has no category: it loads after consent to analytics or marketing (tags in the container are
// controlled by Consent Mode and the cc_consent_update event).
const COOKIE_CONSENT_SERVICES = [
    'ga4' => ['Google Analytics', 'Google', 'a', '/^G-[A-Z0-9]{4,}$/', 'G-XXXXXXXXXX'],
    'clarity' => ['Microsoft Clarity', 'Microsoft', 'a', '/^[a-z0-9]{6,16}$/', 'abcd123456'],
    'hotjar' => ['Hotjar', 'Hotjar', 'a', '/^\d{4,10}$/', '1234567'],
    'ads' => ['Google Ads', 'Google', 'm', '/^AW-\d{6,}$/', 'AW-123456789'],
    'meta' => ['Meta Pixel', 'Meta', 'm', '/^\d{10,20}$/', '123456789012345'],
    'tiktok' => ['TikTok Pixel', 'TikTok', 'm', '/^[A-Z0-9]{10,30}$/', 'C4ABCDEFGHIJ12345678'],
    'linkedin' => ['LinkedIn Insight Tag', 'LinkedIn', 'm', '/^\d{3,12}$/', '1234567'],
    'gtm' => ['Google Tag Manager', 'Google', '', '/^GTM-[A-Z0-9]{4,}$/', 'GTM-XXXXXXX'],
];

// Texts shown by the plugin: key => [admin label, default text]. Custom values are stored in the settings
// (`texts`); empty = default. The message is handled separately (`texts.message`, see cookie_consent_message()).
const COOKIE_CONSENT_TEXTS = [
    'btn_accept' => ['“Accept” button (one category)', 'Accept'],
    'btn_reject' => ['“Reject” button (one category)', 'Reject'],
    'btn_accept_all' => ['“Accept all” button (several categories)', 'Accept all'],
    'btn_reject_all' => ['“Reject all” button (several categories)', 'Reject all'],
    'btn_settings' => ['“Settings” button', 'Settings'],
    'btn_save' => ['“Save choices” button', 'Save choices'],
    'btn_open' => ['Button that reopens the banner', 'Cookies'],
    'privacy_link' => ['Privacy policy link', 'Privacy policy'],
    'legend' => ['Category list heading', 'Choose what you agree to'],
    'cat_n_name' => ['Necessary: name', 'Necessary'],
    'cat_n_desc' => ['Necessary: description', 'remembering your choice'],
    'cat_a_name' => ['Analytics: name', 'Analytics'],
    'cat_a_desc' => ['Analytics: description', 'to see how you use the site and improve it'],
    'cat_m_name' => ['Marketing: name', 'Marketing'],
    'cat_m_desc' => ['Marketing: description', 'to measure ad performance and tailor ads to you'],
    'cat_f_name' => ['Functional: name', 'Functional'],
    'cat_f_desc' => ['Functional: description', 'to show embedded videos and maps'],
    'embed_text' => ['Embed placeholder: text ({service} = YouTube / Google Maps)', 'Content from {service} sets cookies. It will appear once you accept functional cookies.'],
    'embed_button' => ['Embed placeholder: button', 'Accept and show'],
    'aria_banner' => ['Banner label for screen readers', 'Cookie consent'],
    'aria_open' => ['Reopen button label for screen readers', 'Cookie settings'],
];

// Settings (Settings → Cookies) with defaults.
function cookie_consent_settings(): array
{
    $defaults = array_fill_keys(array_keys(COOKIE_CONSENT_SERVICES), '') + [
        'embeds' => 0,          // block embedded YouTube and Google Maps until functional consent
        'texts' => [],          // custom texts (keys from COOKIE_CONSENT_TEXTS)
        'btn_bg' => '#1428a0',
        'btn_fg' => '#ffffff',
        'full_width' => 0,
        'font_size' => 14,
        'test_mode' => 0,       // load services outside production too
        'version' => 1,         // bump = ask everyone for consent again
    ];
    return wp_parse_args(get_option('cookie_consent', []), $defaults);
}

// Text: custom from the settings or the default.
function cookie_consent_text($s, $key): string
{
    $custom = $s['texts'][$key] ?? '';
    return $custom !== '' ? $custom : COOKIE_CONSENT_TEXTS[$key][1];
}

// Services load in production or in test mode. wp_get_environment_type() exists since WP 5.5; older WP = production.
function cookie_consent_services_enabled($s): bool
{
    return $s['test_mode'] || !function_exists('wp_get_environment_type') || wp_get_environment_type() === 'production';
}

// Active categories => service names (only services with an ID; GTM alone counts as analytics).
function cookie_consent_active($s): array
{
    $active = [];
    foreach (COOKIE_CONSENT_SERVICES as $key => $svc) {
        if ($s[$key] !== '' && $svc[2] !== '') {
            $active[$svc[2]][] = $svc[0];
        }
    }
    if ($s['gtm'] !== '' && !$active) {
        $active['a'][] = 'Google Tag Manager';
    }
    if ($s['embeds']) {
        $active['f'] = ['YouTube', 'Google Maps'];
    }
    $ordered = [];
    foreach (array_keys(COOKIE_CONSENT_CATEGORIES) as $cat) {
        if (!empty($active[$cat])) {
            $ordered[$cat] = $active[$cat];
        }
    }
    return $ordered;
}

// Providers of the enabled services (for the message).
function cookie_consent_providers($s): array
{
    $providers = [];
    foreach (COOKIE_CONSENT_SERVICES as $key => $svc) {
        if ($s[$key] !== '') {
            $providers[$svc[1]] = true;
        }
    }
    if ($s['embeds']) {
        $providers['Google'] = true;
    }
    return array_keys($providers);
}

// Message: custom ({services} and {providers} replaced with the lists) or the default.
function cookie_consent_message($s): string
{
    $active = cookie_consent_active($s);
    $services = implode(', ', array_merge(...array_values($active)));
    $providers = cookie_consent_providers($s);
    $custom = $s['texts']['message'] ?? '';
    return $custom !== ''
        ? strtr($custom, ['{services}' => $services, '{providers}' => implode(', ', $providers)])
        : cookie_consent_render_default_consent($active, $services, $providers);
}

// Default English message composed from the enabled services. With several categories it is short: the purposes
// are listed under the banner's “Settings” (second layer of information).
function cookie_consent_render_default_consent(array $active, string $services, array $providers): string
{
    if (count($active) === 1) {
        $cat = key($active);
        $what = mb_strtolower(COOKIE_CONSENT_TEXTS["cat_{$cat}_name"][1]) . ' (' . $services . ') – ' . COOKIE_CONSENT_TEXTS["cat_{$cat}_desc"][1];
    } else {
        $goals = array_map(function ($cat): string { return COOKIE_CONSENT_CATEGORIES[$cat]; }, array_keys($active));
        $what = $services . ' – for ' . implode(', ', array_slice($goals, 0, -1)) . ' and ' . end($goals) . ' purposes (details under “Settings”)';
    }
    return 'With your consent, we use cookies and similar technologies from third-party providers: ' . $what . '. '
        . (count($providers) > 1 ? 'The providers (' . implode(', ', $providers) . ')' : 'The provider (' . $providers[0] . ')')
        . ' may transfer data outside the European Economic Area, including to the USA. You can change your consent at any time using the “Cookies” button in the corner of the page.';
}

// Renders a template from templates/ with prepared values (the template only escapes and prints).
function cookie_consent_render(string $template, array $vars): string
{
    extract($vars);
    ob_start();
    include __DIR__ . "/templates/{$template}.php";
    return ob_get_clean();
}

if (is_admin()) {
    require_once __DIR__ . '/admin.php';
}

add_action('wp_enqueue_scripts', function (): void {
    $s = cookie_consent_settings();
    if (!cookie_consent_active($s)) {
        return;
    }
    $ids = [];
    if (cookie_consent_services_enabled($s)) {
        foreach (array_keys(COOKIE_CONSENT_SERVICES) as $key) {
            if ($s[$key] !== '') {
                $ids[$key] = $s[$key];
            }
        }
    }
    $url = plugin_dir_url(__FILE__);
    wp_enqueue_style('cookie-consent', $url . 'consent.css', [], COOKIE_CONSENT_ASSETS);
    // In <head>, without defer: the Consent Mode default must be set before any tag.
    wp_enqueue_script('cookie-consent', $url . 'consent.js', [], COOKIE_CONSENT_ASSETS, false);
    wp_add_inline_script('cookie-consent', 'window.cookieConsentConfig = ' . wp_json_encode([
        'ids' => (object) $ids,
        'categories' => array_keys(cookie_consent_active($s)),
        'version' => (int) $s['version'],
    ]) . ';', 'before');
}, 1);

// Embed placeholder until functional consent; preg_replace_callback callback from template_redirect.
// $m: 1 = attributes before src, 2 = quote, 3 = URL, 4 = host, 5 = attributes after src. src is moved to data-cc-src.
function cookie_consent_render_embed_cover(array $m): string
{
    $s = cookie_consent_settings();
    $iframe = '<iframe' . $m[1] . ' data-cc-src=' . $m[2] . $m[3] . $m[2] . $m[5] . '></iframe>';
    $fixedWidth = preg_match('~\swidth=["\']?\d+["\']?(\s|$|>)~', $m[1] . ' ' . $m[5]);
    $text = strtr(cookie_consent_text($s, 'embed_text'), ['{service}' => stripos($m[4], 'you') === 0 ? 'YouTube' : 'Google Maps']);
    return '<span class="cc-embed' . ($fixedWidth ? ' cc-embed-fixed' : '') . '">' . $iframe
        . '<span class="cc-embed-ph"><span>' . esc_html($text) . '</span>'
        . '<button type="button" class="cc-btn" data-cc="embed">' . esc_html(cookie_consent_text($s, 'embed_button')) . '</button></span></span>';
}

add_action('wp_footer', function (): void {
    $s = cookie_consent_settings();
    $active = cookie_consent_active($s);
    if (!$active) {
        return;
    }
    $text = function (string $key) use ($s): string { return cookie_consent_text($s, $key); };
    $multi = count($active) > 1;
    $categories = [];
    foreach ($multi ? $active : [] as $cat => $names) {
        $categories[] = ['key' => $cat, 'name' => $text("cat_{$cat}_name"), 'services' => implode(', ', $names), 'desc' => $text("cat_{$cat}_desc")];
    }
    echo cookie_consent_render('banner', [
        'fullWidth' => (bool) $s['full_width'],
        'style' => sprintf('--cc-btn-bg:%s;--cc-btn-fg:%s;--cc-font:%dpx', $s['btn_bg'], $s['btn_fg'], $s['font_size']),
        'ariaBanner' => $text('aria_banner'),
        'message' => cookie_consent_message($s),
        'privacyUrl' => get_privacy_policy_url(),
        'privacyLink' => $text('privacy_link'),
        'categories' => $categories,
        'legend' => $text('legend'),
        'necessaryName' => $text('cat_n_name'),
        'necessaryDesc' => $text('cat_n_desc'),
        'btnSettings' => $text('btn_settings'),
        'btnSave' => $text('btn_save'),
        'btnReject' => $text($multi ? 'btn_reject_all' : 'btn_reject'),
        'btnAccept' => $text($multi ? 'btn_accept_all' : 'btn_accept'),
        'btnOpen' => $text('btn_open'),
        'ariaOpen' => $text('aria_open'),
    ]);
});

// Functional category: YouTube / Google Maps iframes get their src only after consent (placeholder until then).
add_action('template_redirect', function (): void {
    $s = cookie_consent_settings();
    if (!$s['embeds'] || is_feed() || wp_doing_ajax()) {
        return;
    }
    ob_start(function ($html): string {
        return preg_replace_callback(
            '~<iframe\b([^>]*?)\ssrc=(["\'])(https?://(?:www\.)?(youtube\.com|youtube-nocookie\.com|youtu\.be|google\.[a-z.]+/maps|maps\.google\.[a-z.]+)[^"\']*)\2([^>]*)>\s*</iframe>~i',
            'cookie_consent_render_embed_cover',
            $html
        ) ?? $html;
    });
});
