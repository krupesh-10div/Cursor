# iCart Dynamic Landing

Dynamic landing page that adapts content based on search keywords using GPT and WooCommerce product mapping.

## Features
- Shortcode: `[icart_dynamic_page]`
- GPT-generated heading, subheading, explanation, and CTA tailored to user search keywords (`s`, `q`, or `keywords` query params)
- CSV-driven keyword → product mapping with support for IDs, SKUs, tags, and categories
- WooCommerce product grid for dynamic and static sections
- Caching with transients
- Admin settings for API key, model, brand tone, Figma link, cache TTL, static product IDs, and CSV upload

## Installation
1. Upload the `icart-dynamic-landing` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to Settings → iCart Dynamic Landing and:
   - Enter your OpenAI API key and choose a model
   - Set your brand tone and optional Figma link reference
   - Set a cache TTL
   - Add static product IDs (comma separated) for the static section
   - Upload your CSV keyword mapping (see `sample/icart_dynamic_keywords.csv`)
4. Add the shortcode `[icart_dynamic_page]` to a page that uses your landing layout.

## CSV Format
Columns supported:
- `keywords` (required for a row): space/comma/pipe-separated tokens that must all match
- `product_ids` (comma/pipe separated numeric IDs)
- `product_skus` (comma/pipe separated SKUs)
- `product_tags` (comma/pipe separated tag slugs)
- `product_cats` (comma/pipe separated category slugs)

Example rows in `sample/icart_dynamic_keywords.csv`.

## Query Parameters
- `s` or `q` or `keywords` → user-entered search terms

## Notes
- If no API key is set, the plugin falls back to a sensible default copy.
- WooCommerce is optional; if missing, product sections will be hidden.

## Figma
Configure your Figma link in Settings for reference only.

