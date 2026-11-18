# Cookie Consent

A small WordPress plugin that shows a cookie consent banner and loads third-party services only after the visitor agrees.
It supports Google Consent Mode v2 and has no dependencies and no build step: copy the folder to `wp-content/plugins/` and activate it.

## Features

- **Consent categories:** analytics, marketing and functional. The banner only shows categories that have at least one service
  configured, and does not appear at all if nothing is configured.
- **Popular services, configured by ID only** (Settings → Cookies):

  | Category | Services |
  |---|---|
  | Analytics | Google Analytics 4, Microsoft Clarity, Hotjar |
  | Marketing | Google Ads, Meta Pixel, TikTok Pixel, LinkedIn Insight Tag |
  | Functional | blocks embedded YouTube videos and Google Maps until consent, with a placeholder and an accept button |
  | Google Tag Manager | loads after consent to analytics or marketing |

- **Basic Consent Mode:** nothing is sent to any provider before consent. Consent Mode defaults to `denied` and is updated
  per category (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`).
- **Equal choice:** "Reject" and "Accept" always look the same, and no category is pre-selected.
- **Withdrawing consent** with the "Cookies" button in the corner deletes that category's first-party cookies (e.g. `_ga`, `_fbp`)
  and reloads the page without its scripts.
- **All texts are editable.** English defaults; leave a field empty to use the default. The message can use `{services}` and
  `{providers}`, and the embed placeholder `{service}`.
- **Appearance:** button colours, full-width bar or compact box, font size.
- **Ask again:** changing the configured services, or ticking "Ask everyone for consent again", bumps the consent version.
- **Non-production safety:** services do not load unless `WP_ENVIRONMENT_TYPE` is `production` (or "Test mode" is on), so
  staging sites do not pollute production analytics. The banner itself works everywhere.

## Requirements

- WordPress with `wp_head()` and `wp_footer()` in the theme.
- PHP 7.1 or newer.
- Modern browsers (no Internet Explorer support).

## Installation

1. Copy this folder to `wp-content/plugins/cookie-consent/` and activate the plugin.
2. Go to **Settings → Cookies** and enter the IDs of the services the site uses.
3. Remove the same services' tags from the theme and other plugins. Otherwise they keep running without consent.
4. Set a privacy policy page in **Settings → Privacy**: the banner links to it.

## How it works

- `consent.js` runs in `<head>`, sets Consent Mode defaults and, if a decision is already stored, applies it immediately.
- The decision is stored in the first-party cookie `cookie_consent` for 12 months: `v<version>.<granted categories>`,
  e.g. `v3.af` (analytics + functional) or `v3.` (rejected). A different version means no decision, so the banner shows again.
- On every decision the plugin pushes `{event: 'cc_consent_update', cc_analytics, cc_marketing, cc_functional}` to `dataLayer`.
  Google tags in a GTM container respect Consent Mode by themselves. Fire third-party tags in GTM on this event, with a condition
  on `cc_marketing` / `cc_analytics`.
- Embedded YouTube / Google Maps iframes in the page HTML get their `src` moved to `data-cc-src` and a placeholder on top until
  functional consent is given.
- No database writes on the front end and no consent log: settings are a single `cookie_consent` option.

## Limitations

- Cookies set on providers' own domains (e.g. facebook.com) can only be removed by the browser.
- Only plain `<iframe>` embeds in the HTML are blocked. Videos or maps injected by JavaScript are not.
- No custom scripts: only the services listed above.
- The plugin provides the technical part. The texts, the privacy policy and the choice of services remain the site owner's
  responsibility. This is not legal advice.

## Files

```
cookie-consent.php   settings, banner and embed rendering, script loading
admin.php            Settings → Cookies: saving and preparing values
consent.js           Consent Mode, service loaders, banner behaviour
consent.css          banner and placeholder styles (scoped, resistant to theme CSS)
templates/           banner.php, admin.php (output only, values prepared in PHP)
```

## License

[MIT](LICENSE) © 2026 Adam Serbakowski. You may use, modify and distribute the code, including commercially, as long as
the copyright notice and the license text are kept.
