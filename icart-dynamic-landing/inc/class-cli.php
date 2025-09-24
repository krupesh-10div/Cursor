<?php
if (!defined('ABSPATH')) {
	exit;
}

if (defined('WP_CLI') && WP_CLI) {
	/**
	 * WP-CLI commands for iCart Dynamic Landing.
	 */
	class ICartDL_CLI_Command {
		/**
		 * Build sample/content/landing-content.json from current landing map (no API calls).
		 *
		 * ## EXAMPLES
		 *
		 * wp icart-dl build-json
		 */
		public function build_json($args, $assoc_args) {
			icart_dl_build_json_from_landing_map();
			\WP_CLI::success('Built per-product JSON files in sample/content/ from landing_map.');
		}

		/**
		 * Enrich per-product JSON titles and descriptions using OpenAI (ChatGPT).
		 * Respects settings: model and brand_tone.
		 *
		 * ## OPTIONS
		 *
		 * [--force]
		 * : Overwrite existing non-empty title/description.
		 *
		 * ## EXAMPLES
		 *
		 * wp icart-dl enrich-json
		 * wp icart-dl enrich-json --force
		 */
		public function enrich_json($args, $assoc_args) {
			$opts = icart_dl_get_settings();
			$api_key = isset($opts['openai_api_key']) ? $opts['openai_api_key'] : '';
			if (empty($api_key) && isset($opts['perplexity_api_key'])) { $api_key = $opts['perplexity_api_key']; }
			$brand_tone = isset($opts['brand_tone']) ? $opts['brand_tone'] : '';
			$force = !empty($assoc_args['force']);

			if (empty($api_key)) {
				\WP_CLI::error('OpenAI API key not set in settings.');
				return;
			}
			$entries = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
			if (empty($entries)) {
				$entries = icart_dl_scan_sample_keywords();
			}
			if (empty($entries)) {
				\WP_CLI::warning('No entries found to enrich.');
				return;
			}

			$by_product = array();
			foreach ($entries as $row) {
				$product_key = isset($row['product_key']) ? sanitize_title($row['product_key']) : 'default';
				if (!isset($by_product[$product_key])) { $by_product[$product_key] = array(); }
				$by_product[$product_key][] = $row;
			}

			$total_updated = 0;
			foreach ($by_product as $product_key => $rows) {
				$existing = icart_dl_load_content_map_for_product($product_key);
				$map = is_array($existing) ? $existing : array();
				$updated = 0;
				foreach ($rows as $row) {
					$slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
					$keywords = isset($row['keywords']) ? sanitize_text_field($row['keywords']) : '';
					if ($slug === '') { continue; }
					$current_title = isset($map[$slug]['title']) ? trim($map[$slug]['title']) : '';
					$current_short = isset($map[$slug]['short_description']) ? trim($map[$slug]['short_description']) : '';
					if (!$force && $current_title !== '' && $current_short !== '') { continue; }
					list($title, $short) = icart_dl_generate_title_short_openai($keywords, array('slug' => $slug, 'product_key' => $product_key));
					$map[$slug] = array(
						'slug' => $slug,
						'url' => trailingslashit(home_url('/' . $slug)),
						'keywords' => $keywords,
						'title' => $title !== '' ? $title : $current_title,
						'short_description' => $short !== '' ? $short : $current_short,
					);
					$updated++;
					\WP_CLI::log("Updated {$product_key}/{$slug}");
				}
				if ($updated > 0) {
					icart_dl_write_content_map_for_product($product_key, $map);
					$total_updated += $updated;
				}
			}

			if ($total_updated > 0) {
				\WP_CLI::success("Enriched {$total_updated} entries across products and wrote JSON files.");
			} else {
				\WP_CLI::log('No entries updated.');
			}
		}
	}

	\WP_CLI::add_command('icart-dl', 'ICartDL_CLI_Command');
}

?>

