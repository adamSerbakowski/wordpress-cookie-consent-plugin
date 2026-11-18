(() => {
  const CONFIG = window.cookieConsentConfig || { ids: {}, categories: [] };
  const CONSENT_COOKIE = 'cookie_consent';
  const CONSENT_PATTERN = /(?:^|; )cookie_consent=v(\d+)\.([amf]{0,3})(?:;|$)/;
  const ONE_YEAR = 31536000;
  const SERVICES_BY_CATEGORY = { a: ['ga4', 'clarity', 'hotjar'], m: ['ads', 'meta', 'tiktok', 'linkedin'] };
  const COOKIES_BY_CATEGORY = { a: /^(_ga|_gid|_gat|_clck|_clsk|_hj)/, m: /^(_gcl|_fbp|_fbc|_ttp|_tt_|li_fat_id)/ };
  const SERVICE_LOADERS = {
    ga4: loadGoogleTag, ads: loadGoogleTag, gtm: loadTagManager, clarity: loadClarity, hotjar: loadHotjar,
    meta: loadMetaPixel, tiktok: loadTiktokPixel, linkedin: loadLinkedinInsight,
  };
  const TIKTOK_METHODS = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie'];
  const loaded = new Set();

  // --- Always ---
  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function () { dataLayer.push(arguments); };
  gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied' });
  document.addEventListener('click', handleClick);

  // --- Saved decision: banner closed / no decision: banner open ---
  const consent = readConsent();
  if (consent !== null) applyConsent(consent);
  onDomReady(() => document.getElementById('cc-banner') && (consent === null ? openBanner() : closeBanner()));

  // --- Consent ---

  function readConsent() {
    const match = document.cookie.match(CONSENT_PATTERN);
    return match && +match[1] === CONFIG.version ? match[2] : null;
  }

  function saveConsent(categories) {
    const previous = readConsent() || '';
    const granted = CONFIG.categories.filter((cat) => categories.includes(cat)).join('');
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${CONSENT_COOKIE}=v${CONFIG.version}.${granted}; Max-Age=${ONE_YEAR}; path=/; SameSite=Lax${secure}`;
    closeBanner();
    const revoked = [...previous].filter((cat) => !granted.includes(cat));
    if (!revoked.length) return applyConsent(granted);
    revoked.forEach((cat) => COOKIES_BY_CATEGORY[cat] && deleteCookies(COOKIES_BY_CATEGORY[cat]));
    location.reload();
  }

  function applyConsent(granted) {
    const analytics = granted.includes('a');
    const marketing = granted.includes('m');
    gtag('consent', 'update', {
      analytics_storage: consentState(analytics),
      ad_storage: consentState(marketing), ad_user_data: consentState(marketing), ad_personalization: consentState(marketing),
    });
    dataLayer.push({ event: 'cc_consent_update', cc_analytics: analytics, cc_marketing: marketing, cc_functional: granted.includes('f') });
    [...granted].forEach((cat) => (SERVICES_BY_CATEGORY[cat] || []).forEach(loadService));
    if (analytics || marketing) loadService('gtm');
    if (granted.includes('f')) onDomReady(showEmbeds);
  }

  function consentState(granted) {
    return granted ? 'granted' : 'denied';
  }

  // --- Services ---

  function loadService(key) {
    const id = CONFIG.ids[key];
    if (!id || loaded.has(key)) return;
    loaded.add(key);
    SERVICE_LOADERS[key](id);
  }

  function loadGoogleTag(id) {
    if (!loaded.has('gtag.js')) {
      loaded.add('gtag.js');
      addScript(`https://www.googletagmanager.com/gtag/js?id=${id}`);
      gtag('js', new Date());
    }
    gtag('config', id);
  }

  function loadTagManager(id) {
    dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    addScript(`https://www.googletagmanager.com/gtm.js?id=${id}`);
  }

  function loadClarity(id) {
    window.clarity = window.clarity || function () { (window.clarity.q = window.clarity.q || []).push(arguments); };
    addScript(`https://www.clarity.ms/tag/${id}`);
  }

  function loadHotjar(id) {
    window.hj = window.hj || function () { (window.hj.q = window.hj.q || []).push(arguments); };
    window._hjSettings = { hjid: +id, hjsv: 6 };
    addScript(`https://static.hotjar.com/c/hotjar-${id}.js?sv=6`);
  }

  function loadMetaPixel(id) {
    const fbq = window.fbq = window._fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
    Object.assign(fbq, { push: fbq, loaded: true, version: '2.0', queue: [] });
    addScript('https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', id);
    fbq('track', 'PageView');
  }

  function loadTiktokPixel(id) {
    const src = 'https://analytics.tiktok.com/i18n/pixel/events.js';
    const ttq = window.ttq = window.ttq || [];
    TIKTOK_METHODS.forEach((method) => { ttq[method] = (...args) => ttq.push([method, ...args]); });
    Object.assign(ttq, { _i: { [id]: Object.assign([], { _u: src }) }, _t: { [id]: Date.now() }, _o: { [id]: {} } });
    addScript(`${src}?sdkid=${id}&lib=ttq`);
    ttq.page();
  }

  function loadLinkedinInsight(id) {
    window._linkedin_partner_id = id;
    window._linkedin_data_partner_ids = [...(window._linkedin_data_partner_ids || []), id];
    window.lintrk = window.lintrk || Object.assign((a, b) => window.lintrk.q.push([a, b]), { q: [] });
    addScript('https://snap.licdn.com/li.lms-analytics/insight.min.js');
  }

  // --- Banner ---

  function handleClick(event) {
    const action = event.target.closest('[data-cc]')?.dataset.cc;
    if (action === 'granted') saveConsent(CONFIG.categories.join(''));
    else if (action === 'denied') saveConsent('');
    else if (action === 'save') saveConsent(checkedCategories());
    else if (action === 'embed') saveConsent(`${readConsent() || ''}f`);
    else if (action === 'settings') showCategories(true);
    else if (action === 'open') {
      openBanner();
      document.querySelector('#cc-banner button:not([hidden])').focus();
    }
  }

  function openBanner() {
    document.getElementById('cc-banner').hidden = false;
    document.getElementById('cc-open').hidden = true;
    showCategories(readConsent() !== null);
  }

  function closeBanner() {
    document.getElementById('cc-banner').hidden = true;
    document.getElementById('cc-open').hidden = false;
  }

  function showCategories(expanded) {
    const list = document.querySelector('.cc-cats');
    if (!list) return;
    const consent = readConsent() || '';
    const settings = document.querySelector('[data-cc="settings"]');
    list.hidden = !expanded;
    document.querySelector('[data-cc="save"]').hidden = !expanded;
    settings.hidden = expanded;
    settings.setAttribute('aria-expanded', expanded);
    categoryCheckboxes().forEach((box) => { box.checked = consent.includes(box.dataset.ccCat); });
  }

  function checkedCategories() {
    return categoryCheckboxes().filter((box) => box.checked).map((box) => box.dataset.ccCat).join('');
  }

  function categoryCheckboxes() {
    return [...document.querySelectorAll('[data-cc-cat]')];
  }

  // --- Helpers ---

  function showEmbeds() {
    document.querySelectorAll('iframe[data-cc-src]').forEach((frame) => {
      frame.src = frame.dataset.ccSrc;
      frame.removeAttribute('data-cc-src');
      frame.parentNode.querySelector('.cc-embed-ph')?.remove();
    });
  }

  function deleteCookies(pattern) {
    const parts = location.hostname.split('.');
    const domains = parts.slice(0, -1).map((_, i) => `; domain=.${parts.slice(i).join('.')}`).concat('');
    document.cookie.split('; ').map((cookie) => cookie.split('=')[0]).filter((name) => pattern.test(name))
      .forEach((name) => domains.forEach((domain) => { document.cookie = `${name}=; Max-Age=0; path=/${domain}`; }));
  }

  function addScript(src) {
    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
  }

  function onDomReady(callback) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback);
    else callback();
  }
})();
