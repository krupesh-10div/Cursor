# iCart Dynamic Landing

Dynamic landing page that adapts content based on search keywords using Perplexity and keywords-defined landing URLs (no WooCommerce dependency).

## Features
- Shortcode: `[icart_dynamic_page]`
- Perplexity-generated heading, subheading, explanation, and CTA tailored to user search keywords (`s`, `q`, or `keywords` query params)
- Optional static products textarea in settings for always-on items
- Caching with transients
- Admin settings for API key, model, brand tone, Figma link, cache TTL, static products, and uploads
- Landing map CSV or Keywords TXT/CSV to create root-level SEO URLs that all route to a single landing page

## Installation
1. Upload the `icart-dynamic-landing` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to Settings → iCart Dynamic Landing and:
   - Enter your Perplexity API key and choose a model
   - Set your brand tone and optional Figma link reference
   - Set a cache TTL
   - Add static products (one per line) in the format `Title|URL|ImageURL|Price` (optional)
   - Upload your Keywords TXT/CSV (see `sample/keywords.txt`) to auto-generate landing URLs
   - Or upload your Landing Map CSV (see `sample/landing_map.csv`) to control slugs/metadata
4. Add the shortcode `[icart_dynamic_page]` to a page that uses your landing layout.

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

