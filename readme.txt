=== Cookie Consent ===
Contributors: adamserbakowski
Tags: cookie consent, cookie banner, gdpr, consent mode, google analytics
Requires at least: 4.9.6
Tested up to: 7.1
Requires PHP: 7.1
Stable tag: 2.2.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Lightweight cookie consent banner with Google Consent Mode v2. Analytics, marketing pixels and embeds load only after the visitor agrees.

== Description ==

Cookie Consent shows a compact consent banner and loads third-party services only after the visitor agrees to the relevant category. There are no dependencies, no build step and no tracking of its own.

**Consent categories**

* **Analytics:** Google Analytics 4, Microsoft Clarity, Hotjar
* **Marketing:** Google Ads, Meta Pixel, TikTok Pixel, LinkedIn Insight Tag
* **Functional:** embedded YouTube videos and Google Maps are blocked until consent, with a placeholder and an accept button
* **Google Tag Manager** loads after consent to analytics or marketing

You only enter the IDs of the services the site uses. The banner shows only the categories that have at least one service, and does not appear at all if nothing is configured.

**Features**

* Basic Google Consent Mode v2: nothing is sent to any provider before consent. Defaults are `denied` and are updated per category.
* Equal choice: "Reject" and "Accept" always look the same, and no category is pre-selected.
* Visitors can change their decision at any time with the "Cookies" button. Withdrawing consent deletes that category's first-party cookies and reloads the page without its scripts.
* Every text on the banner can be changed in the settings (English defaults). The message supports `{services}` and `{providers}` placeholders.
* Button colours, full-width bar or compact box, font size.
* "Ask everyone again": changing the services, or ticking the option, makes the banner show again for all visitors.
* Staging safety: services load only when `WP_ENVIRONMENT_TYPE` is `production` (or test mode is on), so test sites do not pollute production analytics.
* A `cc_consent_update` event is pushed to `dataLayer` on every decision, for third-party tags in Google Tag Manager.
* No consent log and no front-end database writes. The decision is kept in a first-party cookie.

The plugin provides the technical side of consent. The banner texts, the privacy policy and the choice of services remain the site owner's responsibility. This is not legal advice.

== Installation ==

1. Upload the `cookie-consent` folder to `/wp-content/plugins/`, or install the ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Go to Settings → Cookies and enter the IDs of the services the site uses.
4. Remove the same services' tags from the theme and other plugins, otherwise they keep running without consent.
5. Set a privacy policy page in Settings → Privacy. The banner links to it.

== Frequently Asked Questions ==

= Does it block tracking scripts that are already in my theme or other plugins? =

No. The plugin only controls the services you configure in its settings. Remove other copies of these tags, otherwise they run without consent.

= Why do services not load on my staging site? =

Services load only when `WP_ENVIRONMENT_TYPE` is `production`. On other environments the banner works, but nothing is sent to the providers. To test the loading, turn on "Test mode" in the settings and use test accounts.

= How do I use it with Google Tag Manager? =

Enter the container ID. Google tags in the container respect Consent Mode by themselves. Fire third-party tags on the `cc_consent_update` event with a condition on the data layer variables `cc_analytics` or `cc_marketing` equal to `true`.

= Can I translate the banner? =

Yes. Every text is a setting. Leave a field empty to use the English default.

= Where is the visitor's decision stored? =

In the first-party cookie `cookie_consent`, for 12 months. The plugin does not store consents on the server.

= Which embeds are blocked? =

Plain `<iframe>` embeds of YouTube and Google Maps in the page HTML. Embeds injected later by JavaScript are not blocked.

== External services ==

The plugin connects to the services below only if you enter their ID in the settings, and only after the visitor consents to the service's category. The visitor's browser then loads the provider's script, which may set cookies and send data such as the IP address, browser details and pages visited to the provider.

* **Google Analytics 4, Google Ads, Google Tag Manager** (Google LLC): loads `https://www.googletagmanager.com/gtag/js` or `https://www.googletagmanager.com/gtm.js`. [Terms](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).
* **YouTube and Google Maps embeds** (Google LLC): the original iframe is loaded after functional consent. [Terms](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).
* **Microsoft Clarity** (Microsoft Corporation): loads `https://www.clarity.ms/tag/`. [Terms](https://clarity.microsoft.com/terms), [Privacy Statement](https://privacy.microsoft.com/privacystatement).
* **Hotjar** (Hotjar Ltd): loads `https://static.hotjar.com/c/hotjar-<ID>.js`. [Terms](https://www.hotjar.com/legal/policies/terms-of-service/), [Privacy Policy](https://www.hotjar.com/legal/policies/privacy/).
* **Meta Pixel** (Meta Platforms, Inc.): loads `https://connect.facebook.net/en_US/fbevents.js`. [Terms](https://www.facebook.com/legal/terms), [Privacy Policy](https://www.facebook.com/privacy/policy/).
* **TikTok Pixel** (TikTok): loads `https://analytics.tiktok.com/i18n/pixel/events.js`. [Terms](https://www.tiktok.com/legal/terms-of-service), [Privacy Policy](https://www.tiktok.com/legal/privacy-policy).
* **LinkedIn Insight Tag** (LinkedIn Corporation): loads `https://snap.licdn.com/li.lms-analytics/insight.min.js`. [User Agreement](https://www.linkedin.com/legal/user-agreement), [Privacy Policy](https://www.linkedin.com/legal/privacy-policy).

== Changelog ==

= 2.2.2 =
* First public release.
