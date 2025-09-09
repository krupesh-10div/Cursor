# iCart Dynamic Landing

Dynamic landing page that adapts content based on search keywords using Perplexity and keywords-defined landing URLs (no WooCommerce dependency).

## Features
- No page or shortcode needed; URLs map directly to a plugin template
- Perplexity-generated heading, subheading, explanation, and CTA tailored to user search keywords (`s`, `q`, or `keywords` query params)
- Caching with transients
- Admin settings for API key, model, brand tone, Figma link, cache TTL
- Auto-scan sample keyword CSVs in `sample/keywords/*.csv` to create root-level SEO URLs that all route to a single landing page

## Installation
1. Upload the `icart-dynamic-landing` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to Settings → iCart Dynamic Landing and:
   - Enter your Perplexity API key and choose a model
   - Set your brand tone and optional Figma link reference
   - Set a cache TTL
   - Place product-specific keyword CSVs in `wp-content/plugins/icart-dynamic-landing/sample/keywords/` as `<product_key>.csv` (first column keywords)
   - Optional: upload your Landing Map CSV (see `sample/landing_map.csv`) to control slugs/metadata instead of auto-scan
4. No shortcode needed; visit your keyword slugs directly.

## Keywords Upload
- TXT: one keyword per line
- CSV: first column contains keywords
- The plugin will generate slugs and rewrite rules for root-level URLs

## Query Parameters
- `s` or `q` or `keywords` → user-entered search terms
- Pretty URL: configure base path in settings, e.g., `solutions/best-boost-average-order-value-shopify-2025` → extracted keywords `best-boost-average-order-value-shopify-2025`
- Root SEO URLs from Landing Map/Keywords: each `slug` becomes `/slug` and routes to your landing page

## Notes
- If no API key is set, the plugin falls back to a sensible default copy.
- After changing the base path, go to Settings → Permalinks and click Save to flush.
- After uploading Landing Map or Keywords, permalinks are auto-flushed; if not, save permalinks manually.

## Figma
Configure your Figma link in Settings for reference only.

