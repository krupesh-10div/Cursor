# iCart Dynamic Landing

Dynamic landing page that adapts content based on search keywords using Perplexity and CSV-defined product entries (no WooCommerce dependency).

## Features
- Shortcode: `[icart_dynamic_page]`
- Perplexity-generated heading, subheading, explanation, and CTA tailored to user search keywords (`s`, `q`, or `keywords` query params)
- CSV-driven keyword → product mapping with columns for titles, URLs, images, and prices
- Static products textarea in settings for always-on items
- Caching with transients
- Admin settings for API key, model, brand tone, Figma link, cache TTL, static products, and CSV upload

## Installation
1. Upload the `icart-dynamic-landing` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to Settings → iCart Dynamic Landing and:
   - Enter your Perplexity API key and choose a model
   - Set your brand tone and optional Figma link reference
   - Set a cache TTL
   - Add static products (one per line) in the format `Title|URL|ImageURL|Price`
   - Upload your CSV keyword mapping (see `sample/icart_dynamic_keywords.csv`)
4. Add the shortcode `[icart_dynamic_page]` to a page that uses your landing layout.

## CSV Format (non-WooCommerce)
Columns supported:
- `keywords` (required for a row): space/comma/pipe-separated tokens that must all match
- `product_titles` (pipe-separated list)
- `product_urls` (pipe-separated list)
- `product_images` (pipe-separated list)
- `product_prices` (pipe-separated list)

Example rows in `sample/icart_dynamic_keywords.csv`.

## Query Parameters
- `s` or `q` or `keywords` → user-entered search terms
 - Pretty URL: configure base path in settings, e.g., `solutions/best-boost-average-order-value-shopify-2025` → extracted keywords `best-boost-average-order-value-shopify-2025`

## Notes
- If no API key is set, the plugin falls back to a sensible default copy.
- If a CSV row matches, up to `limit` products are displayed from that row. Duplicates by URL are removed.
 - After changing the base path, go to Settings → Permalinks and click Save to flush.

## Figma
Configure your Figma link in Settings for reference only.

