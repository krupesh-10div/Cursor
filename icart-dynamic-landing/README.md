# iCart Dynamic Landing

Dynamic landing page that adapts content based on search keywords using ChatGPT (OpenAI) and keywords-defined landing URLs (no WooCommerce dependency).

## Features
- No page or shortcode needed; URLs map directly to a plugin template
- ChatGPT-generated heading, subheading, explanation, and CTA tailored to user search keywords (`s`, `q`, or `keywords` query params)
- Caching with transients
- Admin settings for API key, model, brand tone, cache TTL
- Auto-scan sample keyword CSVs in `sample/keywords/*.csv` to create root-level SEO URLs that all route to a single landing page

## Installation
1. Upload the `icart-dynamic-landing` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Go to Settings → iCart Dynamic Landing and:
   - Enter your OpenAI API key and choose a model (e.g., gpt-4o-mini)
   - Set your brand tone
   - Set a cache TTL
   - Place product-specific keyword CSVs in `wp-content/plugins/icart-dynamic-landing/sample/keywords/` as `<product_key>.csv` (first column keywords)
4. No shortcode needed; visit your keyword slugs directly.

## Fast JSON-Based Workflow

1. Upload your keywords CSV(s). Files are stored under `sample/keywords/`.
2. Optionally check "Generate JSON file after upload" next to the filename field to automatically create/update per-product JSON files (titles + descriptions generated via ChatGPT) like `sample/content/icart.json`, `sample/content/steller.json`, `sample/content/tablepress.json`.
3. The plugin reads `title` and `short_description` from the respective product JSON for each slug. No API calls are made at runtime.

JSON entry structure (per product file):

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
- Use WP-CLI to build the JSON if needed (no enrichment at runtime):

```
wp icart-dl build-json
```

- If an entry is missing in JSON, the plugin falls back to a sensible default copy.
- After uploading Keywords, permalinks are auto-flushed; if not, save permalinks manually.


