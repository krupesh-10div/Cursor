# iCart Dynamic Landing

Dynamic landing page that adapts content based on search keywords using Perplexity and keywords-defined landing URLs (no WooCommerce dependency).

## Features
- No page or shortcode needed; URLs map directly to a plugin template
- Perplexity-generated heading, subheading, explanation, and CTA tailored to user search keywords (`s`, `q`, or `keywords` query params)
- Caching with transients
- Admin settings for API key, model, brand tone, cache TTL
- Auto-scan sample keyword CSVs in `sample/keywords/*.csv` to create root-level SEO URLs that all route to a single landing page

## Installation
1. Upload the `icart-dynamic-landing` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to Settings → iCart Dynamic Landing and:
   - Enter your Perplexity API key and choose a model
   - Set your brand tone
   - Set a cache TTL
   - Place product-specific keyword CSVs in `wp-content/plugins/icart-dynamic-landing/sample/keywords/` as `<product_key>.csv` (first column keywords)
4. No shortcode needed; visit your keyword slugs directly.

## Fast JSON-Based Workflow (No API Calls)

1. In Settings → iCart Dynamic Landing, check "Disable API Calls".
2. Upload your keywords CSV(s). Files are stored under `sample/keywords/`.
3. Click "Build landing-content.json" to generate `sample/content/landing-content.json`.
4. The plugin will read `title` and `short_description` from this JSON for each slug.

JSON entry structure:

```
{
  "your-slug": {
    "slug": "your-slug",
    "url": "https://example.com/your-slug/",
    "keywords": "Your Keyword",
    "title": "Title goes here",
    "short_description": "Short description goes here"
  }
}
```

## Keywords Upload
- TXT: one keyword per line
- CSV: first column contains keywords
- The plugin will generate slugs and rewrite rules for root-level URLs

## Query Parameters
- `s` or `q` or `keywords` → user-entered search terms
- Root SEO URLs from Keywords: each `slug` becomes `/slug` and routes to your landing page

## Notes
- Use WP-CLI to build/enrich the JSON:

```
wp icart-dl build-json
wp icart-dl enrich-json
wp icart-dl enrich-json --force
```

- If no API key is set, the plugin falls back to a sensible default copy.
- After uploading Keywords, permalinks are auto-flushed; if not, save permalinks manually.


